<?php

namespace App\Actions\Academic;

use App\Enums\ReportCardStatus;
use App\Models\AcademicPeriod;
use App\Models\ClassRoom;
use App\Models\ReportCard;
use App\Models\School;
use App\Services\Academic\AcademicCalculationService;
use App\Services\AuditService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GenerateReportCards
{
    public function __construct(
        private AcademicCalculationService $calculator,
        private AuditService $audit,
    ) {
    }

    /**
     * @return array{generated: int, skipped: int, report_cards: array<int, ReportCard>}
     */
    public function handle(
        School $school,
        ClassRoom $classRoom,
        AcademicPeriod $period,
        int $actorId,
        ?int $studentId = null,
        bool $newVersion = false,
    ): array {
        if (
            (int) $school->id !== (int) $classRoom->school_id
            || (int) $school->id !== (int) $period->school_id
            || (int) $period->academic_year_id !== (int) $school->academicYears()->whereKey($period->academic_year_id)->value('id')
        ) {
            throw new InvalidArgumentException('Classe, période et école incompatibles.');
        }

        /*
         * Une période close est définitive : on ne produit plus de bulletin
         * pour elle tant qu'elle n'a pas été rouverte par un administrateur.
         */
        if ($period->is_closed) {
            throw new InvalidArgumentException('La période est close : rouvrez-la avant de générer des bulletins.');
        }

        $calculation = $this->calculator->calculateClassResults($classRoom, $period, (int) $school->id);
        $rows = collect($calculation['students']);
        if ($studentId !== null) {
            $rows = $rows->where('student_id', $studentId)->values();
            if ($rows->isEmpty()) {
                throw new InvalidArgumentException('L’élève n’appartient pas à la classe sélectionnée.');
            }
        }

        $rowsWithResults = $rows->filter(fn (array $row) => $row['general_average'] !== null)->values();
        if ($rowsWithResults->isEmpty()) {
            throw new InvalidArgumentException('Aucun résultat calculable : vérifiez que des évaluations validées contiennent des notes.');
        }

        $setting = $school->settings()->withoutGlobalScopes()->first();
        $academicYear = $period->academicYear;
        $generated = [];

        DB::transaction(function () use (
            $school,
            $classRoom,
            $period,
            $academicYear,
            $setting,
            $rowsWithResults,
            $actorId,
            $newVersion,
            &$generated
        ) {
            foreach ($rowsWithResults as $row) {
                $latest = ReportCard::withoutGlobalScopes()
                    ->where('school_id', $school->id)
                    ->where('student_id', $row['student_id'])
                    ->where('academic_period_id', $period->id)
                    ->orderByDesc('version')
                    ->first();

                if ($latest?->status === ReportCardStatus::Published && ! $newVersion) {
                    throw new InvalidArgumentException('Un bulletin est déjà publié. Utilisez la régénération versionnée.');
                }

                $preservedComments = $latest && $latest->status !== ReportCardStatus::Published
                    ? $latest->items()->pluck('teacher_comment', 'subject_id')
                    : collect();

                $version = $latest
                    ? ($latest->status === ReportCardStatus::Published ? $latest->version + 1 : $latest->version)
                    : 1;

                $snapshot = [
                    'school' => [
                        'name' => $setting?->school_name ?: $school->name,
                        'address' => $setting?->address,
                        'city' => $setting?->city,
                        'country' => $setting?->country,
                        'phone' => $setting?->phone,
                        'email' => $setting?->email,
                        'website' => $setting?->website,
                        'slogan' => $setting?->slogan,
                        'logo_path' => $setting?->logo_path,
                        'primary_color' => $setting?->primary_color,
                    ],
                    'student' => [
                        'id' => $row['student_id'],
                        'name' => $row['student_name'],
                        'matricule' => $row['matricule'],
                    ],
                    'class' => ['id' => $classRoom->id, 'name' => $classRoom->name],
                    'academic_year' => ['id' => $academicYear->id, 'name' => $academicYear->name],
                    'period' => [
                        'id' => $period->id,
                        'name' => $period->name,
                        'starts_at' => $period->starts_at?->format('Y-m-d'),
                        'ends_at' => $period->ends_at?->format('Y-m-d'),
                    ],
                    'generated_at' => now()->toIso8601String(),
                    'observations' => null,
                    'signatures' => [
                        'teacher' => null,
                        'censeur' => null,
                        'head' => null,
                    ],
                ];

                $attributes = [
                    'school_id' => $school->id,
                    'student_id' => $row['student_id'],
                    'class_room_id' => $classRoom->id,
                    'academic_year_id' => $academicYear->id,
                    'academic_period_id' => $period->id,
                    'version' => $version,
                    'status' => ReportCardStatus::Generated,
                    'general_average' => $row['general_average'],
                    'rank' => $row['rank'],
                    'total_students' => $row['ranked_students'],
                    'appreciation' => $this->calculator->appreciation($row['general_average']),
                    'attendance_summary' => $row['attendance'],
                    'snapshot' => $snapshot,
                    'verification_token' => $latest && $latest->status !== ReportCardStatus::Published
                        ? $latest->verification_token
                        : Str::random(64),
                    'generated_at' => now(),
                    'generated_by' => $actorId,
                    'published_at' => null,
                    'published_by' => null,
                    'pdf_path' => null,
                    'pdf_generated_at' => null,
                ];

                $reportCard = $latest && $latest->status !== ReportCardStatus::Published
                    ? tap($latest)->update($attributes)
                    : ReportCard::create($attributes);

                if (! $reportCard instanceof ReportCard) {
                    $reportCard = $latest->refresh();
                }

                $reportCard->items()->delete();
                foreach ($row['subjects'] as $item) {
                    $reportCard->items()->create([
                        'subject_id' => $item['subject_id'],
                        'subject_name' => $item['subject_name'],
                        'coefficient' => $item['coefficient'],
                        'average' => $item['average'],
                        'evaluation_count' => $item['evaluation_count'],
                        'rank' => null,
                        'appreciation' => $item['appreciation'],
                        'teacher_comment' => $preservedComments->get($item['subject_id']),
                    ]);
                }

                $event = $latest?->status === ReportCardStatus::Published
                    ? 'report_card.regenerated'
                    : 'report_card.generated';
                $this->audit->log($event, $reportCard, [
                    'student_id' => $reportCard->student_id,
                    'class_room_id' => $classRoom->id,
                    'academic_period_id' => $period->id,
                    'version' => $version,
                    'source' => 'validated_evaluations_only',
                ]);

                $generated[] = $reportCard->fresh(['items']);
            }
        });

        return [
            'generated' => count($generated),
            'skipped' => $rows->count() - $rowsWithResults->count(),
            'report_cards' => $generated,
        ];
    }
}
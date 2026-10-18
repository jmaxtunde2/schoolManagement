<?php

namespace App\Actions\Academic\EducMaster;

use App\Models\EvaluationResult;
use App\Models\School;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Export des notes d'évaluation au format Excel pour EducMaster.
 */
class EducMasterEvaluationNotesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    /**
     * @param  array<int, int>|null  $allowedClassIds
     */
    public function __construct(
        private School $school,
        private ?int $academicYearId = null,
        private ?int $classId = null,
        private ?int $periodId = null,
        private ?array $allowedClassIds = null,
    ) {}

    public function collection(): Collection
    {
        return EvaluationResult::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->whereHas('evaluation', function ($query) {
                $query->where('school_id', $this->school->id)
                    ->when($this->academicYearId, fn ($q) => $q->where('academic_year_id', $this->academicYearId))
                    ->when($this->classId, fn ($q) => $q->where('class_id', $this->classId))
                    ->when($this->periodId, fn ($q) => $q->where('academic_period_id', $this->periodId))
                    ->when($this->allowedClassIds !== null, fn ($q) => $q->whereIn('class_id', $this->allowedClassIds));
            })
            ->with([
                'evaluation.subject:id,name',
                'evaluation.classRoom:id,name',
                'evaluation.academicPeriod:id,name',
                'evaluation.academicYear:id,name',
                'student:id,matricule,first_name,last_name',
            ])
            ->get()
            ->sortBy(fn (EvaluationResult $result) => [
                (string) $result->evaluation?->classRoom?->name,
                (string) $result->evaluation?->subject?->name,
                (string) $result->student?->last_name,
                (string) $result->student?->first_name,
            ])
            ->values();
    }

    public function headings(): array
    {
        return [
            'Année scolaire',
            'Période',
            'Classe',
            'Matière',
            'Évaluation',
            'Type',
            'Date',
            'Barème',
            'Coefficient',
            'Matricule',
            'Nom',
            'Prénom(s)',
            'Note',
            'Absent',
            'Statut évaluation',
        ];
    }

    public function map($result): array
    {
        $evaluation = $result->evaluation;
        $student = $result->student;

        return [
            $evaluation?->academicYear?->name ?? '',
            $evaluation?->academicPeriod?->name ?? '',
            $evaluation?->classRoom?->name ?? '',
            $evaluation?->subject?->name ?? '',
            $evaluation?->title ?? '',
            $evaluation?->type?->label() ?? '',
            $evaluation?->evaluation_date?->format('d/m/Y') ?? '',
            $evaluation?->max_score ?? '',
            $evaluation?->coefficient ?? '',
            $student?->matricule ?? '',
            $student?->last_name ?? '',
            $student?->first_name ?? '',
            $result->is_absent ? '' : $result->score,
            $result->is_absent ? 'Oui' : 'Non',
            $evaluation?->status?->label() ?? '',
        ];
    }

    public function title(): string
    {
        return 'Notes';
    }
}

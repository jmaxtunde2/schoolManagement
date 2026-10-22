<?php

namespace App\Actions\Evaluations;

use App\Enums\EvaluationStatus;
use App\Models\AcademicPeriod;
use App\Models\Evaluation;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\UserNotifications\EvaluationNotificationService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateEvaluation
{
    public function __construct(
        private readonly EvaluationNotificationService $notifications
    ) {}

    public function handle(Teacher $teacher, array $data, int $actorId, bool $validateDirectly = false): Evaluation
    {
        $academicYearId = $teacher->school->academicYears()->where('is_current', true)->value('id')
            ?? $teacher->school->academicYears()->latest('id')->value('id');

        if (! $academicYearId) {
            throw new RuntimeException("Aucune année scolaire n'est configurée pour cet établissement. Contactez l'administration.");
        }

        $academicPeriodId = $this->currentPeriodId($teacher->school_id, $academicYearId);

        return DB::transaction(function () use ($teacher, $data, $academicYearId, $academicPeriodId, $actorId, $validateDirectly) {
            $evaluation = Evaluation::create([
                ...$data,
                'teacher_id' => $teacher->id,
                'academic_year_id' => $academicYearId,
                'academic_period_id' => $academicPeriodId,
                'created_by' => $actorId,
                'entered_by' => $actorId,
                'status' => $validateDirectly ? EvaluationStatus::InProgress->value : 'draft',
                'submitted_at' => $validateDirectly ? now() : null,
                'submitted_by' => $validateDirectly ? $actorId : null,
            ]);

            foreach (Student::where('class_id', $evaluation->class_id)->where('is_active', true)->orderBy('last_name')->orderBy('first_name')->pluck('id') as $studentId) {
                $evaluation->results()->create(['school_id' => $evaluation->school_id, 'student_id' => $studentId]);
            }

            $this->notifications->notify($evaluation->fresh(['teacher.user', 'classRoom', 'subject']), 'evaluation.created');

            return $evaluation->fresh(['teacher.user', 'classRoom', 'subject']);
        });
    }

    private function currentPeriodId(int $schoolId, int $academicYearId): ?int
    {
        return AcademicPeriod::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('is_closed', false)
            ->whereDate('starts_at', '<=', now())
            ->whereDate('ends_at', '>=', now())
            ->orderBy('position')
            ->value('id');
    }
}

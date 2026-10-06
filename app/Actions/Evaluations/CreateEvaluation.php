<?php

namespace App\Actions\Evaluations;

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

    public function handle(Teacher $teacher, array $data, int $actorId): Evaluation
    {
        $academicYearId = $teacher->school->academicYears()->where('is_current', true)->value('id')
            ?? $teacher->school->academicYears()->latest('id')->value('id');

        if (! $academicYearId) {
            throw new RuntimeException("Aucune année scolaire n'est configurée pour cet établissement. Contactez l'administration.");
        }

        return DB::transaction(function () use ($teacher, $data, $academicYearId, $actorId) {
            $evaluation = Evaluation::create([
                ...$data,
                'teacher_id' => $teacher->id,
                'academic_year_id' => $academicYearId,
                'created_by' => $actorId,
                'entered_by' => $actorId,
                'status' => 'draft',
            ]);

            foreach (Student::where('class_id', $evaluation->class_id)->where('is_active', true)->orderBy('last_name')->orderBy('first_name')->pluck('id') as $studentId) {
                $evaluation->results()->create(['school_id' => $evaluation->school_id, 'student_id' => $studentId]);
            }

            $this->notifications->notify($evaluation->fresh(['teacher.user', 'classRoom', 'subject']), 'evaluation.created');

            return $evaluation->fresh(['teacher.user', 'classRoom', 'subject']);
        });
    }
}

<?php

namespace App\Services\Assistant;

use App\Enums\EvaluationStatus;
use App\Models\Evaluation;

/**
 * Suivi des enseignants : évaluations dont les notes ne sont pas encore
 * saisies ou validées.
 */
class SchoolTeacherService
{
    public function __construct(private AssistantContext $context) {}

    /**
     * Évaluations non validées du périmètre courant.
     *
     * @return array<int, array{teacher: string, class: string, subject: string, evaluation: string, status: string}>
     */
    public function pendingEvaluations(): array
    {
        return Evaluation::query()
            ->whereIn('status', [
                EvaluationStatus::Draft->value,
                EvaluationStatus::InProgress->value,
                EvaluationStatus::Returned->value,
            ])
            ->when(
                $this->context->allowedClassIds !== null,
                fn ($query) => $query->whereIn('class_id', $this->context->allowedClassIds),
            )
            ->with([
                'teacher.user:id,name',
                'classRoom:id,name',
                'subject:id,name',
            ])
            ->latest('evaluation_date')
            ->get()
            ->map(fn (Evaluation $evaluation) => [
                'teacher' => $evaluation->teacher?->user?->name ?? 'Non assigné',
                'class' => $evaluation->classRoom?->name ?? '—',
                'subject' => $evaluation->subject?->name ?? '—',
                'evaluation' => $evaluation->title,
                'status' => $evaluation->status->label(),
            ])
            ->values()
            ->all();
    }

    /** Nombre d'enseignants distincts concernés par une saisie en attente. */
    public function pendingTeacherCount(): int
    {
        return Evaluation::query()
            ->whereIn('status', [
                EvaluationStatus::Draft->value,
                EvaluationStatus::InProgress->value,
                EvaluationStatus::Returned->value,
            ])
            ->when(
                $this->context->allowedClassIds !== null,
                fn ($query) => $query->whereIn('class_id', $this->context->allowedClassIds),
            )
            ->whereNotNull('teacher_id')
            ->distinct()
            ->count('teacher_id');
    }
}

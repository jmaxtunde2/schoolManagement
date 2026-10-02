<?php

namespace App\Actions\Evaluations;

use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Services\AuditService;
use App\Services\UserNotifications\EvaluationNotificationService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubmitEvaluation
{
    public function __construct(
        private AuditService $audit,
        private EvaluationNotificationService $notifications
    ) {
    }

    public function handle(
        Evaluation $evaluation,
        int $userId
    ): Evaluation {
        return DB::transaction(function () use (
            $evaluation,
            $userId
        ) {
            $evaluation = Evaluation::withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($evaluation->id);

            $previousStatus = $evaluation->status;

            if (! in_array($evaluation->status, [EvaluationStatus::Draft, EvaluationStatus::Returned], true)) {
                throw new RuntimeException(
                    'Seule une évaluation en brouillon ou retournée peut être soumise.'
                );
            }

            $total = $evaluation->results()->count();

            if ($total === 0) {
                throw new RuntimeException(
                    'Cette évaluation ne contient aucun élève.'
                );
            }

            $invalidScores = $evaluation
                ->results()
                ->where('is_absent', false)
                ->where(function ($query) use ($evaluation) {
                    $query
                        ->whereNull('score')
                        ->orWhere('score', '<', 0)
                        ->orWhere(
                            'score',
                            '>',
                            $evaluation->max_score
                        );
                })
                ->exists();

            if ($invalidScores) {
                throw new RuntimeException(
                    'Certaines notes sont absentes ou dépassent le barème autorisé.'
                );
            }

            $graded = $evaluation
                ->results()
                ->where(function ($query) {
                    $query
                        ->where('is_absent', true)
                        ->orWhereNotNull('score');
                })
                ->count();

            if ($graded < $total) {
                throw new RuntimeException(
                    'Toutes les notes doivent être saisies avant soumission.'
                );
            }

            $evaluation->update([
                'status' => EvaluationStatus::InProgress->value,
                'submitted_at' => now(),
                'submitted_by' => $userId,
                'entered_by' => $evaluation->entered_by ?: $userId,
                'return_reason' => null,
            ]);

            $this->audit->log(
                'evaluation.submitted',
                $evaluation,
                [
                    'submitted_by' => $userId,
                    'previous_status' => $previousStatus?->value ?? $previousStatus,
                ]
            );

            $notificationEvent = $previousStatus === EvaluationStatus::Returned
                ? 'evaluation.resubmitted'
                : 'evaluation.submitted';

            $this->notifications->notify(
                $evaluation->fresh(['teacher.user', 'subject', 'classRoom']),
                $notificationEvent
            );

            return $evaluation->fresh(['teacher.user', 'subject', 'classRoom']);
        });
    }
}
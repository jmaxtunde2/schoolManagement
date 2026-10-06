<?php

namespace App\Actions\Evaluations;

use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Services\AuditService;
use App\Services\Notifications\NotificationService;
use App\Services\UserNotifications\EvaluationNotificationService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReturnEvaluation
{
    public function __construct(
        private NotificationService $notifications,
        private AuditService $audit,
        private EvaluationNotificationService $evaluationNotifications
    ) {}

    public function handle(
        Evaluation $evaluation,
        int $userId,
        string $reason
    ): Evaluation {
        $reason = trim($reason);

        if ($reason === '') {
            throw new RuntimeException(
                'Le motif du retour est obligatoire.'
            );
        }

        $returned = DB::transaction(function () use (
            $evaluation,
            $userId,
            $reason
        ) {
            $evaluation = Evaluation::withoutGlobalScopes()
                ->lockForUpdate()
                ->findOrFail($evaluation->id);

            if (
                $evaluation->status !== EvaluationStatus::InProgress
            ) {
                throw new RuntimeException(
                    'Seule une évaluation en attente de validation peut être retournée.'
                );
            }

            $evaluation->update([
                'status' => EvaluationStatus::Returned->value,
                'returned_at' => now(),
                'returned_by' => $userId,
                'return_reason' => $reason,
            ]);

            $this->audit->log(
                'evaluation.returned',
                $evaluation,
                [
                    'returned_by' => $userId,
                    'reason' => $reason,
                ]
            );

            return $evaluation->fresh([
                'teacher.user',
                'classRoom',
                'subject',
            ]);
        });

        /*
         * La notification est envoyée après la transaction.
         * Ainsi, on ne notifie pas l'enseignant si la transaction
         * de retour a échoué.
         */
        $this->evaluationNotifications->notify(
            $returned,
            'evaluation.returned'
        );

        return $returned;
    }
}

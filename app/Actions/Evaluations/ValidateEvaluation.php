<?php
namespace App\Actions\Evaluations;

use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Services\AuditService;
use App\Services\Notifications\NotificationService;
use App\Services\UserNotifications\EvaluationNotificationService;
use Illuminate\Support\Facades\DB;

class ValidateEvaluation
{
    public function __construct(
        private NotificationService $notifications,
        private AuditService $audit,
        private EvaluationNotificationService $evaluationNotifications
    ) {
    }

    public function handle(Evaluation $evaluation, int $validatorId): Evaluation
    {
        $validated = DB::transaction(function () use ($evaluation, $validatorId) {
            $evaluation = Evaluation::withoutGlobalScopes()->lockForUpdate()->findOrFail($evaluation->id);

            if ($evaluation->status !== EvaluationStatus::InProgress) {
                throw new \RuntimeException('Cette évaluation doit être en cours de validation.');
            }

            $total = $evaluation->results()->count();
            $graded = $evaluation->results()->where(fn ($q) => $q->where('is_absent', true)->orWhereNotNull('score'))->count();

            if ($total === 0 || $graded < $total) {
                throw new \RuntimeException('Toutes les notes doivent être saisies avant validation.');
            }

            $evaluation->update([
                'status' => EvaluationStatus::Validated->value,
                'validated_at' => now(),
                'validated_by' => $validatorId,
            ]);

            $this->audit->log('evaluation.validated', $evaluation, ['validated_by' => $validatorId]);

            return $evaluation->fresh(['teacher.user', 'subject', 'classRoom']);
        });

        $this->notifications->notifyForEvaluation($validated);
        $this->evaluationNotifications->notify($validated, 'evaluation.validated');

        return $validated;
    }
}

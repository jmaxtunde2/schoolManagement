<?php

namespace App\Actions\Evaluations;

use App\Enums\EvaluationNotificationStatus;
use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Models\User;
use App\Services\AuditService;
use App\Services\UserNotifications\EvaluationNotificationService;
use Illuminate\Support\Facades\DB;

class ValidateEvaluation
{
    public function __construct(
        private AuditService $audit,
        private EvaluationNotificationService $evaluationNotifications
    ) {}

    public function handle(Evaluation $evaluation, int $validatorId): Evaluation
    {
        $validated = DB::transaction(function () use ($evaluation, $validatorId) {
            $evaluation = Evaluation::withoutGlobalScopes()
                ->with('enteredBy')
                ->lockForUpdate()
                ->findOrFail($evaluation->id);

            $validator = User::query()->findOrFail($validatorId);
            $censeurDraft = $evaluation->status === EvaluationStatus::Draft
                && $validator->isCenseur()
                && (int) $evaluation->entered_by === $validatorId
                && $evaluation->enteredBy?->isCenseur();

            if ($evaluation->status !== EvaluationStatus::InProgress && ! $censeurDraft) {
                throw new \RuntimeException('Cette évaluation ne peut pas être validée dans son état actuel.');
            }

            $total = $evaluation->results()->count();
            $graded = $evaluation->results()->where(fn ($q) => $q->where('is_absent', true)->orWhereNotNull('score'))->count();

            if ($total === 0 || $graded < $total) {
                throw new \RuntimeException('Toutes les notes doivent être saisies avant validation.');
            }

            // Les parents ne sont pas notifiés immédiatement : l'évaluation est
            // marquée « en attente » puis consolidée par le traitement quotidien
            // (app/Services/Communication/NotificationAggregationService).
            $evaluation->update([
                'status' => EvaluationStatus::Validated->value,
                'validated_at' => now(),
                'validated_by' => $validatorId,
                'notification_status' => EvaluationNotificationStatus::Pending->value,
                'notified_at' => null,
            ]);

            $this->audit->log('evaluation.validated', $evaluation, ['validated_by' => $validatorId]);

            return $evaluation->fresh(['teacher.user', 'subject', 'classRoom']);
        });

        $this->evaluationNotifications->notify($validated, 'evaluation.validated');

        return $validated;
    }
}

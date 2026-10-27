<?php

namespace App\Jobs;

use App\Enums\EvaluationNotificationStatus;
use App\Enums\NotificationStatus;
use App\Models\Evaluation;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload)
    {
    }

    public function handle(): void
    {
        $entries = $this->payload['entry'] ?? [];

        foreach ($entries as $entry) {
            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $value = $change['value'] ?? [];
                $statuses = $value['statuses'] ?? [];

                foreach ($statuses as $status) {
                    $this->processStatus($status);
                }
            }
        }
    }

    private function processStatus(array $status): void
    {
        $waId = $status['id'] ?? null;
        if (! $waId) {
            return;
        }

        $notification = Notification::withoutGlobalScopes()
            ->where('external_id', $waId)
            ->first();

        if (! $notification) {
            return;
        }

        $statusValue = $status['status'] ?? null;

        if ($statusValue === 'delivered') {
            $notification->delivered_at = $notification->delivered_at ?: now();
            $notification->save();
            $notification->recordStatus(NotificationStatus::Delivered, 'Livré via WhatsApp (webhook).');
            $this->updateEvaluations($notification, EvaluationNotificationStatus::Delivered);
            return;
        }

        if ($statusValue === 'read') {
            $notification->delivered_at = $notification->delivered_at ?: now();
            $notification->save();
            $notification->recordStatus(NotificationStatus::Delivered, 'Lu via WhatsApp (webhook).');
            $this->updateEvaluations($notification, EvaluationNotificationStatus::Delivered);
            return;
        }

        if ($statusValue === 'failed') {
            $error = $status['errors'][0]['title'] ?? $status['errors'][0]['message'] ?? 'Échec WhatsApp';
            $notification->forceFill(['error' => $error])->save();
            $notification->recordStatus(NotificationStatus::Failed, $error);
            $this->updateEvaluations($notification, EvaluationNotificationStatus::Failed);
            return;
        }

        if ($statusValue === 'sent') {
            // Mark as sent if not already
            if ($notification->status !== NotificationStatus::Sent && $notification->status !== NotificationStatus::Delivered) {
                $notification->recordStatus(NotificationStatus::Sent, 'Envoyé via WhatsApp (webhook).');
                $this->updateEvaluations($notification, EvaluationNotificationStatus::Sent);
            }
            return;
        }
    }

    private function updateEvaluations(Notification $notification, EvaluationNotificationStatus $status): void
    {
        if (! $notification->notification_batch_id) {
            return;
        }

        $batch = $notification->batch;
        if (! $batch) {
            return;
        }

        $evaluationIds = $batch->evaluations()->pluck('evaluations.id');
        if ($evaluationIds->isEmpty()) {
            return;
        }

        $update = ['notification_status' => $status->value];
        if ($status === EvaluationNotificationStatus::Delivered) {
            $update['notified_at'] = $notification->batch->evaluations()->first()?->notified_at ?? now();
        }
        if ($status === EvaluationNotificationStatus::Sent || $status === EvaluationNotificationStatus::Delivered) {
            // ensure notified_at set if not
        }

        Evaluation::withoutGlobalScopes()
            ->whereIn('id', $evaluationIds)
            ->update($update);
    }
}

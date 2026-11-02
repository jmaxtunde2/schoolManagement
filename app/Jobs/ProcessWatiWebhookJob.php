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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessWatiWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload) {}

    public function handle(): void
    {
        $fingerprint = hash('sha256', json_encode([
            'event' => $this->payload['eventType'] ?? $this->payload['statusString'] ?? null,
            'status' => $this->payload['statusString'] ?? null,
            'localMessageId' => $this->payload['localMessageId'] ?? null,
            'whatsappMessageId' => $this->payload['whatsappMessageId'] ?? null,
            'id' => $this->payload['id'] ?? null,
            'failedCode' => $this->payload['failedCode'] ?? null,
            'failedDetail' => $this->payload['failedDetail'] ?? null,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');

        if (! Cache::add('wati-webhook:'.$fingerprint, true, now()->addDay())) {
            Log::debug('Duplicate WATI webhook ignored.', [
                'event' => $this->payload['eventType'] ?? null,
            ]);

            return;
        }

        $this->processStatus($this->payload);
    }

    private function processStatus(array $payload): void
    {
        $notification = $this->findNotification($payload);

        if (! $notification) {
            return;
        }

        $event = strtolower((string) ($payload['eventType'] ?? ''));
        $status = strtolower((string) ($payload['statusString'] ?? ''));

        if (str_contains($event, 'failed') || $status === 'failed') {
            if ($notification->status === NotificationStatus::Delivered) {
                return;
            }

            $detail = (string) ($payload['failedDetail'] ?? 'Échec WhatsApp');
            $code = isset($payload['failedCode']) ? ' ('.$payload['failedCode'].')' : '';
            $error = $detail.$code;

            if ($notification->status === NotificationStatus::Failed && $notification->error === $error) {
                return;
            }

            $notification->forceFill(['error' => $error])->save();
            $notification->recordStatus(NotificationStatus::Failed, $error);
            $this->updateEvaluations($notification, EvaluationNotificationStatus::Failed);

            return;
        }

        if (str_contains($event, 'read') || $status === 'read') {
            $notification->delivered_at = $notification->delivered_at ?: now();
            $notification->save();

            if ($notification->status !== NotificationStatus::Delivered) {
                $notification->recordStatus(NotificationStatus::Delivered, 'Lu via WhatsApp (WATI).');
            }

            $this->updateEvaluations($notification, EvaluationNotificationStatus::Delivered);

            return;
        }

        if (str_contains($event, 'delivered') || $status === 'delivered') {
            $notification->delivered_at = $notification->delivered_at ?: now();
            $notification->save();

            if ($notification->status !== NotificationStatus::Delivered) {
                $notification->recordStatus(NotificationStatus::Delivered, 'Livré via WhatsApp (WATI).');
            }

            $this->updateEvaluations($notification, EvaluationNotificationStatus::Delivered);

            return;
        }

        if (str_contains($event, 'sent') || $status === 'sent') {
            if ($notification->status !== NotificationStatus::Sent && $notification->status !== NotificationStatus::Delivered) {
                $notification->recordStatus(NotificationStatus::Sent, 'Envoyé via WhatsApp (WATI).');
                $this->updateEvaluations($notification, EvaluationNotificationStatus::Sent);
            }
        }
    }

    private function findNotification(array $payload): ?Notification
    {
        foreach (['localMessageId', 'whatsappMessageId', 'id'] as $key) {
            $externalId = $payload[$key] ?? null;

            if (! is_string($externalId) || $externalId === '') {
                continue;
            }

            $notification = Notification::withoutGlobalScopes()
                ->where('provider', 'wati')
                ->where('external_id', $externalId)
                ->first();

            if ($notification) {
                return $notification;
            }
        }

        return null;
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

        Evaluation::withoutGlobalScopes()
            ->whereIn('id', $evaluationIds)
            ->update($update);
    }
}

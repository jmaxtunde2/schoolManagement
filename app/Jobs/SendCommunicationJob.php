<?php

namespace App\Jobs;

use App\Enums\CommunicationChannel;
use App\Enums\EvaluationNotificationStatus;
use App\Enums\NotificationStatus;
use App\Models\Evaluation;
use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Services\Communication\CommunicationBudgetService;
use App\Services\Communication\CommunicationTransport;
use App\Services\Communication\NotificationChannelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Envoie une notification externe (WhatsApp → SMS → Email).
 *
 * La bascule est automatique : si le canal prioritaire n'est pas disponible
 * ou échoue, le suivant est tenté. Le coût n'est débité qu'après succès.
 */
class SendCommunicationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function __construct(public int $notificationId) {}

    public function handle(
        CommunicationTransport $transport,
        CommunicationBudgetService $budget,
        NotificationChannelService $channels,
    ): void {
        $notification = Notification::withoutGlobalScopes()->find($this->notificationId);

        if (! $notification) {
            return;
        }

        if (in_array($notification->status, [NotificationStatus::Sent, NotificationStatus::Delivered], true)) {
            return;
        }

        $school = School::find($notification->school_id);

        if (! $school) {
            $notification->recordStatus(NotificationStatus::Failed, 'Établissement introuvable.');

            return;
        }

        $guardian = $notification->parent_id
            ? ParentGuardian::withoutGlobalScopes()->find($notification->parent_id)
            : null;

        $phone = $notification->phone ?: $guardian?->phone;
        $email = $notification->email ?: $guardian?->email;

        $candidates = $channels->candidates($school, $phone, $email);

        foreach ($candidates as $channel) {
            if (! $this->channelReady($channel, $school, $phone, $email, $budget)) {
                continue;
            }

            $recipient = $channel === CommunicationChannel::Email ? $email : $phone;

            if (! $recipient) {
                continue;
            }

            $notification->increment('attempts');
            $notification->recordStatus(NotificationStatus::Queued);

            $response = $transport->send($channel, $school, $recipient, $notification->message, $notification->metadata);
            $provider = $this->providerName($channel);

            if ($response->success) {
                [$cost, $currency] = $budget->costFor($channel);

                $notification->forceFill([
                    'channel' => $channel->value,
                    'phone' => $channel === CommunicationChannel::Email ? null : $recipient,
                    'email' => $channel === CommunicationChannel::Email ? $recipient : null,
                    'provider' => $provider,
                    'external_id' => $response->externalId,
                    'sent_at' => now(),
                    'error' => null,
                    'estimated_cost' => $cost,
                    'cost_currency' => $currency,
                ])->save();

                $notification->recordStatus(NotificationStatus::Sent, 'Envoyé via '.$channel->label().'.');

                $budget->consume($school, $channel, $cost);
                $this->syncBatch($notification, $cost, $currency);

                return;
            }

            $notification->forceFill(['provider' => $provider, 'error' => $response->error])->save();
            $notification->recordStatus(NotificationStatus::Failed, $response->error);
        }
    }

    private function channelReady(
        CommunicationChannel $channel,
        School $school,
        ?string $phone,
        ?string $email,
        CommunicationBudgetService $budget,
    ): bool {
        return match ($channel) {
            CommunicationChannel::Sms => (bool) $phone && $budget->smsAvailable($school),
            CommunicationChannel::Whatsapp => (bool) $phone && $budget->whatsappAvailable($school),
            CommunicationChannel::Email => (bool) $email && $budget->emailAvailable($school),
            default => false,
        };
    }

    private function providerName(CommunicationChannel $channel): string
    {
        return match ($channel) {
            CommunicationChannel::Sms => (string) config('services.sms.provider', 'log'),
            CommunicationChannel::Whatsapp => 'whatsapp',
            CommunicationChannel::Email => 'mail',
            default => 'internal',
        };
    }

    private function syncBatch(Notification $notification, float $cost, string $currency): void
    {
        if (! $notification->notification_batch_id) {
            return;
        }

        $batch = NotificationBatch::withoutGlobalScopes()->find($notification->notification_batch_id);

        if (! $batch) {
            return;
        }

        $batch->forceFill([
            'cost' => (float) ($batch->cost ?? 0) + $cost,
            'currency' => $currency,
            'status' => NotificationStatus::Sent,
        ])->save();

        Evaluation::withoutGlobalScopes()
            ->whereIn('id', $batch->evaluations()->pluck('evaluations.id'))
            ->whereIn('notification_status', [
                EvaluationNotificationStatus::Included->value,
                EvaluationNotificationStatus::Queued->value,
            ])
            ->update([
                'notification_status' => EvaluationNotificationStatus::Sent->value,
                'notified_at' => now(),
            ]);
    }

    public function failed(Throwable $exception): void
    {
        Notification::withoutGlobalScopes()
            ->find($this->notificationId)
            ?->recordStatus(NotificationStatus::Failed, $exception->getMessage());
    }
}

<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Services\Sms\SmsManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/** Envoie un SMS de manière asynchrone afin de ne jamais bloquer la requête HTTP de validation. */
class SendSmsNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public int $notificationId) {}

    public function handle(SmsManager $sms): void
    {
        $notification = Notification::withoutGlobalScopes()->find($this->notificationId);

        if (! $notification || $notification->channel !== 'sms' || $notification->status === NotificationStatus::Sent || $notification->status === NotificationStatus::Delivered) {
            return;
        }

        $notification->increment('attempts');
        $notification->recordStatus(NotificationStatus::Queued);

        $provider = $sms->provider();
        $response = $provider->send($notification->phone, $notification->message);

        if ($response->success) {
            $notification->forceFill([
                'provider' => $provider->name(),
                'external_id' => $response->externalId,
                'sent_at' => now(),
                'error' => null,
            ])->save();
            $notification->recordStatus(NotificationStatus::Sent);
        } else {
            $notification->forceFill(['provider' => $provider->name(), 'error' => $response->error])->save();
            $notification->recordStatus(NotificationStatus::Failed, $response->error);
        }
    }

    public function failed(Throwable $exception): void
    {
        $notification = Notification::withoutGlobalScopes()->find($this->notificationId);
        $notification?->recordStatus(NotificationStatus::Failed, $exception->getMessage());
    }
}

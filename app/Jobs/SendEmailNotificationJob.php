<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        public int $notificationId
    ) {
    }

    public function handle(): void
    {
        $notification = Notification::withoutGlobalScopes()
            ->find($this->notificationId);

        if (
            ! $notification ||
            $notification->channel !== 'email' ||
            in_array(
                $notification->status->value,
                ['sent', 'delivered'],
                true
            )
        ) {
            return;
        }

        $notification->increment('attempts');

        $notification->recordStatus(
            NotificationStatus::Queued
        );

        try {
            Mail::raw(
                $notification->message,
                function ($mail) use ($notification) {
                    $mail
                        ->to($notification->email)
                        ->subject('Nouvelle note scolaire');
                }
            );

            $notification->forceFill([
                'provider' => config('mail.default'),
                'sent_at' => now(),
                'error' => null,
            ])->save();

            $notification->recordStatus(
                NotificationStatus::Sent
            );
        } catch (Throwable $e) {
            $notification->forceFill([
                'error' => $e->getMessage(),
            ])->save();

            $notification->recordStatus(
                NotificationStatus::Failed,
                $e->getMessage()
            );

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        $notification = Notification::withoutGlobalScopes()
            ->find($this->notificationId);

        if ($notification) {
            $notification->recordStatus(
                NotificationStatus::Failed,
                $e->getMessage()
            );
        }
    }
}
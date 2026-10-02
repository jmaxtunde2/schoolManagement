<?php

namespace App\Jobs;

use App\Models\UserNotification;
use App\Services\Mail\SchoolMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendUserNotificationEmailJob implements ShouldQueue
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
        $notification = UserNotification::with(['user', 'school'])
            ->find($this->notificationId);

        if (! $notification || ! $notification->user || ! $notification->school) {
            return;
        }

        $user = $notification->user;

        if (! $user->email) {
            return;
        }

        try {
            app(SchoolMailService::class)->send(
                $notification->school,
                $user->email,
                $user->name,
                $notification->title,
                $notification->message
            );
        } catch (Throwable $exception) {
            Log::error('Erreur d\'envoi du mail de notification interne', [
                'notification_id' => $notification->id,
                'school_id' => $notification->school_id,
                'user_id' => $user->id,
                'type' => $notification->type,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
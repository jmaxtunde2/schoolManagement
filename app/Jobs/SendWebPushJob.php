<?php

namespace App\Jobs;

use App\Models\UserNotification;
use App\Services\WebPush\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWebPushJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function __construct(
        public int $notificationId
    ) {}

    public function handle(WebPushService $webPush): void
    {
        $notification = UserNotification::withoutGlobalScopes()
            ->find($this->notificationId);

        if (! $notification) {
            return;
        }

        try {
            $webPush->sendNotification($notification);
        } catch (Throwable $exception) {
            Log::error('Erreur d\'envoi Web Push', [
                'notification_id' => $notification->id,
                'school_id' => $notification->school_id,
                'user_id' => $notification->user_id,
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

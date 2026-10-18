<?php

namespace App\Services\UserNotifications;

use App\Jobs\SendUserNotificationEmailJob;
use App\Jobs\SendWebPushJob;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;

class UserNotificationService
{
    /**
     * Create an internal notification and optionally send an email.
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $sendEmail = true,
        bool $sendPush = true,
    ): UserNotification {
        return DB::transaction(function () use (
            $user,
            $type,
            $title,
            $message,
            $data,
            $sendEmail,
            $sendPush
        ) {
            $notification = $this->findExistingNotification($user, $type, $data);

            if (! $notification) {
                $notification = UserNotification::create([
                    'school_id' => $user->school_id,
                    'user_id' => $user->id,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'data' => $data ?: null,
                ]);
            }

            if ($notification->wasRecentlyCreated) {
                if ($sendEmail && $user->email) {
                    SendUserNotificationEmailJob::dispatch($notification->id);
                }

                if (
                    $sendPush
                    && $user->pushSubscriptions()->withoutGlobalScopes()->exists()
                ) {
                    SendWebPushJob::dispatch($notification->id);
                }
            }

            return $notification;
        });
    }

    protected function findExistingNotification(
        User $user,
        string $type,
        array $data = []
    ): ?UserNotification {
        if (empty($data['evaluation_id'])) {
            return null;
        }

        return UserNotification::query()
            ->where('school_id', $user->school_id)
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->whereJsonContains('data->evaluation_id', $data['evaluation_id'])
            ->first();
    }

    /**
     * Send the same notification to several users.
     *
     * @param  iterable<User>  $users
     */
    public function sendToMany(
        iterable $users,
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $sendEmail = true,
        bool $sendPush = true,
    ): int {
        $count = 0;

        foreach ($users as $user) {
            $this->send(
                user: $user,
                type: $type,
                title: $title,
                message: $message,
                data: $data,
                sendEmail: $sendEmail,
                sendPush: $sendPush,
            );

            $count++;
        }

        return $count;
    }
}

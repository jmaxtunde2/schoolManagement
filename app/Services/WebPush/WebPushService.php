<?php

namespace App\Services\WebPush;

use App\Models\PushSubscription;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Envoie des notifications « Web Push » aux abonnements d'un utilisateur.
 *
 * Pointe sur le même flux que les notifications in-app : UserNotificationService
 * délègue ici (via SendWebPushJob). Les abonnements expirés sont purgés.
 */
class WebPushService
{
    public function __construct(private WebPushManager $manager) {}

    public function sendNotification(UserNotification $notification): int
    {
        $user = User::find($notification->user_id);

        if (! $user) {
            return 0;
        }

        return $this->sendToUser($user, [
            'title' => $notification->title,
            'body' => Str::limit(strip_tags($notification->message), 180),
            'url' => $notification->data['url'] ?? null,
            'tag' => $notification->type,
        ]);
    }

    /**
     * @param  array{title?: string, body?: string, url?: ?string, tag?: ?string, icon?: ?string}  $payload
     */
    public function sendToUser(User $user, array $payload): int
    {
        /** @var Collection<int, PushSubscription> $subscriptions */
        $subscriptions = $user->pushSubscriptions()->withoutGlobalScopes()->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $payload = $this->normalize($payload, $user);
        $results = $this->manager->provider()->sendMany($subscriptions, $payload);

        $sent = 0;
        $expired = [];

        foreach ($subscriptions as $subscription) {
            $result = $results[$subscription->endpoint] ?? null;

            if ($result?->success) {
                $sent++;
                $subscription->forceFill(['last_used_at' => now()])->save();

                continue;
            }

            if ($result?->expired) {
                $expired[] = $subscription->endpoint;
            }
        }

        if ($expired !== []) {
            PushSubscription::withoutGlobalScopes()->whereIn('endpoint', $expired)->delete();
        }

        return $sent;
    }

    /**
     * @param  array{title?: string, body?: string, url?: ?string, tag?: ?string, icon?: ?string}  $payload
     * @return array<string, mixed>
     */
    private function normalize(array $payload, User $user): array
    {
        return [
            'title' => $payload['title'] ?? config('app.name'),
            'body' => $payload['body'] ?? '',
            'url' => $this->relativeUrl($payload['url'] ?? null) ?? $this->defaultUrl($user),
            'tag' => $payload['tag'] ?? null,
            'icon' => $payload['icon'] ?? '/icons/icon-192.png',
            'badge' => '/icons/favicon-32.png',
        ];
    }

    private function defaultUrl(User $user): string
    {
        try {
            return route($user->role->homeRoute(), absolute: false);
        } catch (\Throwable) {
            return '/';
        }
    }

    /**
     * Les abonnements sont liés à un origin : on ne transmet qu'un chemin
     * relatif pour que le service worker le résolve sur le bon domaine.
     */
    private function relativeUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (str_starts_with($url, 'http')) {
            $path = parse_url($url, PHP_URL_PATH);

            return $path ?: '/';
        }

        return str_starts_with($url, '/') ? $url : '/'.$url;
    }
}

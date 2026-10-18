<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\WebPush\WebPushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Paramètres de notifications push (PWA) du parent.
 *
 * L'abonnement lui-même est géré côté client (PushSubscriptionController) ;
 * cette page transmet la clé publique VAPID et la liste des appareils abonnés.
 */
class NotificationSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $subscriptions = $request->user()
            ->pushSubscriptions()
            ->withoutGlobalScopes()
            ->latest('last_used_at')
            ->get()
            ->map(fn (PushSubscription $subscription) => [
                'id' => $subscription->id,
                'device' => $this->describeDevice($subscription->user_agent),
                'domain' => $subscription->domain,
                'last_used_at' => $subscription->last_used_at?->diffForHumans(),
            ])
            ->values();

        return Inertia::render('Settings/PushNotifications', [
            'vapidPublicKey' => config('webpush.vapid.public_key') ?: null,
            'subscriptions' => $subscriptions,
        ]);
    }

    public function test(Request $request, WebPushService $webPush): RedirectResponse
    {
        $sent = $webPush->sendToUser($request->user(), [
            'title' => config('app.name'),
            'body' => 'Ceci est une notification de test.',
            'url' => route('parent.notifications.edit', absolute: false),
            'tag' => 'push-test',
        ]);

        return back()->with(
            $sent > 0 ? 'success' : 'error',
            $sent > 0
                ? 'Notification de test envoyée à vos appareils.'
                : "Aucun appareil abonné n'a pu être notifié.",
        );
    }

    private function describeDevice(?string $userAgent): string
    {
        $ua = (string) $userAgent;

        $os = match (true) {
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Appareil',
        };

        $browser = match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'OPR') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => null,
        };

        return $browser ? "{$browser} · {$os}" : $os;
    }
}

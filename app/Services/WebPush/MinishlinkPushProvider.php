<?php

namespace App\Services\WebPush;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription as MinishlinkSubscription;
use Minishlink\WebPush\WebPush;

/**
 * Fournisseur réel : envoi via le protocole Web Push (minishlink/web-push).
 *
 * Les abonnements expirés (endpoint 404/410) sont signalés afin que
 * WebPushService puisse les purger.
 */
class MinishlinkPushProvider implements PushProvider
{
    public function sendMany(iterable $subscriptions, array $payload): array
    {
        $subscriptions = collect($subscriptions);

        if ($subscriptions->isEmpty()) {
            return [];
        }

        $config = config('webpush');

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $config['vapid']['subject'],
                'publicKey' => $config['vapid']['public_key'],
                'privateKey' => $config['vapid']['private_key'],
            ],
        ], [
            'TTL' => (int) $config['ttl'],
        ]);

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                $this->toMinishlinkSubscription($subscription),
                $encoded,
            );
        }

        $results = [];

        foreach ($webPush->flush() as $report) {
            $results[$report->getEndpoint()] = $report->isSuccess()
                ? PushResponse::success()
                : PushResponse::failure($report->getReason(), $report->isSubscriptionExpired());
        }

        // Tout abonnement sans rapport est traité comme un échec non bloquant.
        foreach ($subscriptions as $subscription) {
            $results[$subscription->endpoint] ??= PushResponse::failure('Aucun rapport renvoyé.');
        }

        return $results;
    }

    private function toMinishlinkSubscription(PushSubscription $subscription): MinishlinkSubscription
    {
        return MinishlinkSubscription::create([
            'endpoint' => $subscription->endpoint,
            'publicKey' => $subscription->public_key,
            'authToken' => $subscription->auth_token,
            // Les navigateurs modernes utilisent aes128gcm (RFC 8291).
            'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
        ]);
    }

    public function name(): string
    {
        return 'minishlink';
    }
}

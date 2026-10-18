<?php

namespace App\Services\WebPush;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;

/**
 * Fournisseur par défaut : n'envoie rien, journalise la charge utile.
 * Permet de valider tout le flux (notification -> file -> abonnements)
 * avant le branchement réel via WEBPUSH_PROVIDER=minishlink.
 */
class LogPushProvider implements PushProvider
{
    public function sendMany(iterable $subscriptions, array $payload): array
    {
        $results = [];

        foreach ($subscriptions as $subscription) {
            Log::info('[WebPush:log] Envoi simulé', [
                'endpoint' => $subscription->endpoint,
                'user_id' => $subscription->user_id,
                'payload' => $payload,
            ]);

            $results[$subscription->endpoint] = PushResponse::success();
        }

        return $results;
    }

    public function name(): string
    {
        return 'log';
    }
}

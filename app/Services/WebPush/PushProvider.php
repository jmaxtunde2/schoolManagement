<?php

namespace App\Services\WebPush;

use App\Models\PushSubscription;

/**
 * Abstraction du fournisseur Web Push. Toute implémentation (réel, journalisé,
 * futur relais) doit rester indépendante du reste de l'application. Le
 * fournisseur actif est résolu via `config('webpush.provider')` /
 * la variable WEBPUSH_PROVIDER.
 */
interface PushProvider
{
    /**
     * Envoie la même charge utile à plusieurs abonnements.
     *
     * @param  iterable<PushSubscription>  $subscriptions
     * @param  array<string, mixed>  $payload
     * @return array<string, PushResponse>  Résultats indexés par endpoint.
     */
    public function sendMany(iterable $subscriptions, array $payload): array;

    public function name(): string;
}

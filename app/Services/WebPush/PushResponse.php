<?php

namespace App\Services\WebPush;

/**
 * Résultat d'un envoi Web Push vers un abonnement.
 *
 * `expired` indique que l'abonnement doit être supprimé (endpoint expiré,
 * code HTTP 404/410) : l'utilisateur s'est désabonné ou l'appareil a été
 * réinitialisé.
 */
class PushResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly bool $expired = false,
        public readonly ?string $error = null,
    ) {}

    public static function success(): self
    {
        return new self(true);
    }

    public static function failure(string $error, bool $expired = false): self
    {
        return new self(false, $expired, $error);
    }
}

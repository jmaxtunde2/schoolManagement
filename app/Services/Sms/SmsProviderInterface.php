<?php

namespace App\Services\Sms;

/**
 * Abstraction du fournisseur SMS. Toute intégration (Twilio, Orange, MTN, etc.)
 * doit implémenter cette interface afin que le reste de l'application reste
 * indépendant du fournisseur choisi. Le fournisseur actif est résolu via
 * la configuration `services.sms.provider` / la variable d'env SMS_PROVIDER.
 */
interface SmsProviderInterface
{
    public function send(string $phone, string $message): SmsResponse;

    public function name(): string;
}

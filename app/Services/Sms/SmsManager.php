<?php

namespace App\Services\Sms;

class SmsManager
{
    public function provider(): SmsProviderInterface
    {
        // Point d'extension unique : ajouter un `match` ici pour brancher un vrai
        // fournisseur (Twilio, Orange, MTN...) selon SMS_PROVIDER, sans toucher
        // au reste de l'application (NotificationService, Job, historique).
        return app(SmsProviderInterface::class);
    }
}

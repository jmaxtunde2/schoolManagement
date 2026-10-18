<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fournisseur Web Push
    |--------------------------------------------------------------------------
    |
    | `log` (défaut) journalise les envois sans contacter de service : tout le
    | flux (notification -> file -> abonnements) reste testable sans clés.
    | `minishlink` envoie réellement via le protocole Web Push (RFC 8291) et
    | nécessite la paire de clés VAPID ci-dessous.
    |
    */

    'provider' => env('WEBPUSH_PROVIDER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Clés VAPID
    |--------------------------------------------------------------------------
    |
    | Générer avec :
    |   php -r "require 'vendor/autoload.php'; print_r(Minishlink\WebPush\VAPID::createVapidKeys());"
    | `subject` doit être une adresse mailto: ou une URL https.
    |
    */

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:support@coriyase.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Durée de vie des messages (secondes)
    |--------------------------------------------------------------------------
    |
    | Un message non délivré est conservé par le service de push jusqu'à
    | expiration (défaut : 7 jours).
    |
    */

    'ttl' => (int) env('WEBPUSH_TTL', 60 * 60 * 24 * 7),

];

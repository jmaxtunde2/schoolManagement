<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Parent Access (Code Simplifié)
    |--------------------------------------------------------------------------
    */

    'parent_access' => [
        'max_devices' => env('PARENT_ACCESS_MAX_DEVICES', 1),
        'code_validity_minutes' => env('PARENT_ACCESS_CODE_VALIDITY_MINUTES', 1440),
        // Canal d'envoi des codes : sms (défaut) ou whatsapp.
        'channel' => env('PARENT_ACCESS_CHANNEL', 'sms'),
        // Message SMS : {code} et {url} sont remplacés à l'envoi.
        'sms_message' => env(
            'PARENT_ACCESS_SMS_MESSAGE',
            'CoriSchool : votre accès parent : {code}. Visitez votre espace ici : {url}.'
        ),
        // {slug} est remplacé par le slug de l'établissement.
        'sms_url' => env('PARENT_ACCESS_SMS_URL', 'https://corischool.com/ecole/{slug}'),
        // Fournisseur WhatsApp dédié aux codes d'accès (twilio, wati, meta).
        // Utilisé uniquement si PARENT_ACCESS_CHANNEL=whatsapp.
        // Vide = fournisseur WhatsApp global.
        'whatsapp_provider' => env('PARENT_ACCESS_WHATSAPP_PROVIDER'),
    ],

];

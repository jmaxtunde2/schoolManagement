<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification externe
    |--------------------------------------------------------------------------
    |
    | Une notification externe ne doit jamais dépasser un seul segment SMS.
    | Lorsque les résultats ne tiennent pas dans cette limite, une alerte
    | concise est envoyée et le détail reste disponible dans l'espace parent.
    |
    */

    'sms_max_length' => (int) env('MAX_NOTIFICATION_SMS_LENGTH', 160),

    // Adresse publique affichée dans les messages (espace parent).
    'portal_url' => env('COMMUNICATION_PORTAL_URL', 'corischool.com'),

    /*
    |--------------------------------------------------------------------------
    | Traitement par lots
    |--------------------------------------------------------------------------
    |
    | Les messages externes sont envoyés par lots de 25 destinataires, avec
    | un délai entre chaque lot pour lisser la charge et limiter les coûts.
    |
    */

    'batch_size' => (int) env('COMMUNICATION_BATCH_SIZE', 25),

    'batch_interval_minutes' => (int) env('COMMUNICATION_BATCH_INTERVAL', 10),

    /*
    |--------------------------------------------------------------------------
    | Cycle quotidien
    |--------------------------------------------------------------------------
    |
    | Les évaluations validées avant 17h00 sont consolidées par élève puis
    | envoyées. Les évaluations validées après basculent sur le cycle du
    | lendemain.
    |
    */

    'daily_hour' => env('COMMUNICATION_DAILY_HOUR', '17:00'),

    // Durée de conservation des notifications in-app actives (jours).
    'in_app_retention_days' => (int) env('COMMUNICATION_IN_APP_RETENTION_DAYS', 15),

    /*
    |--------------------------------------------------------------------------
    | Coûts unitaires (comptabilisation)
    |--------------------------------------------------------------------------
    |
    | Valeurs par défaut, surchargées par les réglages de l'école.
    |
    */

    'costs' => [
        'sms' => (float) env('COMMUNICATION_SMS_COST', 25),
        'whatsapp' => (float) env('COMMUNICATION_WHATSAPP_COST', 0.02),
        'email' => (float) env('COMMUNICATION_EMAIL_COST', 0),
    ],

    'currencies' => [
        'sms' => 'XOF',
        'whatsapp' => 'USD',
        'email' => 'XOF',
    ],

    /*
    |--------------------------------------------------------------------------
    | Accès SMS sécurisé à une évaluation
    |--------------------------------------------------------------------------
    |
    | Les SMS ne contiennent plus le détail des notes : ils pointent vers une
    | page publique temporaire, contextualisée côté serveur par un token.
    | La durée de vie et le nombre d'utilisations sont configurables pour
    | pouvoir révoquer ou faire expirer un lien sans redéploiement.
    |
    */

    'sms_access' => [
        'ttl_hours' => (int) env('SMS_ACCESS_TTL_HOURS', 72),
        'token_length' => (int) env('SMS_ACCESS_TOKEN_LENGTH', 32),
        'max_uses' => (int) env('SMS_ACCESS_MAX_USES', 25),

        // Domaine public utilisé dans le SMS (ex. domaine court dédié).
        // Doit être une URL HTTPS complète en production.
        'url' => rtrim((string) env('SMS_ACCESS_URL', env('APP_URL', 'https://corischool.com')), '/'),

        'message' => env(
            'SMS_ACCESS_MESSAGE',
            'CoriSchool : une nouvelle évaluation de votre enfant est disponible. Voir les notes :'
        ),
    ],

];

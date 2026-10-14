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

];

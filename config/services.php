<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'multitexter'),
        'api_url' => env('SMS_API_URL', 'https://sms.esmsafrica.io'),
        'token' => env('ESMS_TOKEN', env('SMS_API_KEY')),
        'api_secret' => env('SMS_API_SECRET'),
        'sender_id' => env('SMS_SENDER_ID'),
        'multitexter' => [
            'email' => env('SMS_EMAIL'),
            'password' => env('SMS_PASS'),
            'sender' => env('SMS_SENDER_NAME') ?: (env('SMS_SENDER_ID') ?: 'LCRS'),
        ],
        'clicksend' => [
            'username' => env('CLICKSEND_USERNAME'),
            'api_key' => env('CLICKSEND_API_KEY'),
            'sender' => env('CLICKSEND_SENDER_ID'),
            'api_url' => env('CLICKSEND_API_URL', 'https://rest.clicksend.com/v3'),
        ],
        // Endpoint facultatif de consultation du solde fournisseur. Si absent,
        // le solde local (school_communication_settings.sms_balance) fait foi.
        'balance_url' => env('SMS_BALANCE_URL'),
        // Indicatif téléphonique utilisée pour normaliser les numéros au format
        // international (E.164) exigé par l'API WhatsApp avant l'envoi.
        'country_code' => env('SMS_COUNTRY_CODE', '228'),
    ],

    /*
    | WhatsApp Business Cloud API (Meta). Activée via SMS_PROVIDER=whatsapp.
    | https://developers.facebook.com/docs/whatsapp/cloud-api
    */
    'whatsapp' => [
    'enabled' => env('WHATSAPP_ENABLED', false),
    'token' => env('WHATSAPP_ACCESS_TOKEN'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com'),
    'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v21.0'),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
],

];

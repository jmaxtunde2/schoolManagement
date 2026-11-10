<?php

$envList = static fn (string $key): array => array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env($key, ''))
), fn (string $value) => $value !== ''));

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
        'provider' => env('SMS_PROVIDER', 'emc'),
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
        'emc' => [
            'enabled' => filter_var(env('EMC_SMS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'api_url' => env('EMC_SMS_API_URL', 'https://api.e-mc.co/v3'),
            'api_key' => env('EMC_SMS_API_KEY'),
            'username' => env('EMC_SMS_USERNAME'),
            'password' => env('EMC_SMS_PASSWORD'),
            'sender' => env('EMC_SMS_SENDER', 'CoriSchool'),
            'timeout' => (int) env('EMC_SMS_TIMEOUT', 15),
            'country_code' => env('EMC_SMS_COUNTRY_CODE', '229'),
        ],
        // Endpoint facultatif de consultation du solde fournisseur. Si absent,
        // le solde local (school_communication_settings.sms_balance) fait foi.
        'balance_url' => env('SMS_BALANCE_URL'),
        // Indicatif téléphonique utilisée pour normaliser les numéros au format
        // international (E.164) exigé par l'API WhatsApp avant l'envoi.
        'country_code' => env('SMS_COUNTRY_CODE', '228'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        'timeout' => (int) env('TWILIO_TIMEOUT', 15),

        'templates' => [
            'verification_code_parent' => [
                'content_sid' => env('TWILIO_PARENT_ACCESS_CODE_CONTENT_SID'),
            ],

            'espace_parent' => [
                'content_sid' => env('TWILIO_PARENT_ACCESS_SPACE_CONTENT_SID'),
            ],

            'corischool_evaluation' => [
                'content_sid' => env('TWILIO_EVALUATION_CONTENT_SID'),
            ],
            'corischool_evaluation_v2' => [
                'content_sid' => env('TWILIO_EVALUATION_V2_CONTENT_SID'),
            ],
        ],
    ],

    /*
    | WhatsApp Business Cloud API (Meta). Activée via SMS_PROVIDER=whatsapp.
    | https://developers.facebook.com/docs/whatsapp/cloud-api
    */
    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'meta'),
        'enabled' => env('WHATSAPP_ENABLED', false),
        'token' => env('WHATSAPP_ACCESS_TOKEN', env('WHATSAPP_TOKEN')),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', env('WHATSAPP_API_VERSION', 'v21.0')),
        'lateness_template_language' => env('WHATSAPP_LATENESS_TEMPLATE_LANGUAGE', 'fr'),
        'parent_access_template_language' => env('WHATSAPP_PARENT_ACCESS_TEMPLATE_LANGUAGE', 'fr'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),

        'wati' => [
            'enabled' => env('WATI_ENABLED', false),
            'api_url' => env('WATI_API_URL', 'https://live-mt-server.wati.io'),
            'api_key' => env('WATI_API_KEY'),
            'channel_number' => env('WATI_CHANNEL_NUMBER'),
            'webhook_secret' => env('WATI_WEBHOOK_SECRET'),
            'timeout' => (int) env('WATI_TIMEOUT', 15),
            'template_cache_ttl' => (int) env('WATI_TEMPLATE_CACHE_TTL', 300),
            'templates' => [
                'corischool_evaluation' => [
                    'name' => env('WATI_EVALUATION_TEMPLATE', 'evaluation_wati_fr'),
                    'language' => env('WATI_EVALUATION_TEMPLATE_LANGUAGE', 'fr'),
                    'body_parameter_names' => $envList('WATI_EVALUATION_BODY_PARAMETERS'),
                    'url_parameter_name' => env('WATI_EVALUATION_URL_PARAMETER'),
                ],
                'corischool_absence' => [
                    'name' => env('WATI_ABSENCE_TEMPLATE', 'corischool_absence'),
                    'language' => env('WATI_ABSENCE_TEMPLATE_LANGUAGE', 'fr'),
                    'body_parameter_names' => $envList('WATI_ABSENCE_BODY_PARAMETERS'),
                    'url_parameter_name' => env('WATI_ABSENCE_URL_PARAMETER'),
                ],
                'template_retard' => [
                    'name' => env('WATI_LATENESS_TEMPLATE', 'template_retard'),
                    'language' => env('WATI_LATENESS_TEMPLATE_LANGUAGE', 'fr'),
                    'body_parameter_names' => $envList('WATI_LATENESS_BODY_PARAMETERS'),
                    'url_parameter_name' => env('WATI_LATENESS_URL_PARAMETER'),
                ],
                'verification_code_parent' => [
                    'name' => env('WATI_PARENT_ACCESS_CODE_TEMPLATE', 'verification_code_parent'),
                    'language' => env('WATI_PARENT_ACCESS_CODE_TEMPLATE_LANGUAGE', 'fr'),
                    'body_parameter_names' => $envList('WATI_PARENT_ACCESS_CODE_BODY_PARAMETERS'),
                    'url_parameter_name' => env('WATI_PARENT_ACCESS_CODE_URL_PARAMETER'),
                ],
                'espace_parent' => [
                    'name' => env('WATI_PARENT_ACCESS_SPACE_TEMPLATE', 'espace_parent'),
                    'language' => env('WATI_PARENT_ACCESS_SPACE_TEMPLATE_LANGUAGE', 'fr'),
                    'body_parameter_names' => $envList('WATI_PARENT_ACCESS_SPACE_BODY_PARAMETERS'),
                    'url_parameter_name' => env('WATI_PARENT_ACCESS_SPACE_URL_PARAMETER'),
                ],
            ],
        ],
    ],

];

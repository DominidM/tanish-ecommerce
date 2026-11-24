<?php

return [
    'ga4' => [
        'measurement_id' => env('GA4_MEASUREMENT_ID'),
        'api_secret' => env('GA4_API_SECRET'),
        'endpoint' => 'https://www.google-analytics.com/mp/collect',
    ],

    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'webhook'),
        'url' => env('WHATSAPP_NOTIFY_URL'),
        'token' => env('WHATSAPP_NOTIFY_TOKEN'),
        'admin_number' => env('WHATSAPP_ADMIN_NUMBER'),
        'meta_token' => env('WHATSAPP_META_TOKEN'),
        'meta_phone_id' => env('WHATSAPP_META_PHONE_ID'),
        'template_name' => env('WHATSAPP_TEMPLATE_NAME'),
        'template_lang' => env('WHATSAPP_TEMPLATE_LANG', 'es'),
    ],
];

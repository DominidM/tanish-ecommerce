<?php

return [
    'ga4' => [
        'measurement_id' => env('GA4_MEASUREMENT_ID'),
        'api_secret' => env('GA4_API_SECRET'),
        'endpoint' => 'https://www.google-analytics.com/mp/collect',
    ],

    'whatsapp' => [
        'url' => env('WHATSAPP_NOTIFY_URL'),
        'token' => env('WHATSAPP_NOTIFY_TOKEN'),
        'admin_number' => env('WHATSAPP_ADMIN_NUMBER'),
    ],
];

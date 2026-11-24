<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Servicio de comercio (WordPress + WooCommerce)
    |--------------------------------------------------------------------------
    |
    | Laravel nunca accede por SQL a las tablas de WordPress. Toda la
    | comunicación pasa por la REST API v3 y por webhooks firmados.
    |
    */

    'base_url' => rtrim((string) env('WOOCOMMERCE_URL', 'http://localhost:8080'), '/'),

    'consumer_key' => env('WOOCOMMERCE_KEY'),

    'consumer_secret' => env('WOOCOMMERCE_SECRET'),

    'webhook_secret' => env('WOOCOMMERCE_WEBHOOK_SECRET'),

    'api_version' => 'wc/v3',

    'per_page' => (int) env('WOOCOMMERCE_PER_PAGE', 100),

    'timeout' => (int) env('WOOCOMMERCE_TIMEOUT', 20),

    'verify' => filter_var(env('WOOCOMMERCE_VERIFY', true), FILTER_VALIDATE_BOOL),
];

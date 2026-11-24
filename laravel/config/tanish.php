<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración propia de TANISH
    |--------------------------------------------------------------------------
    */

    'whatsapp_number' => env('TANISH_WHATSAPP_NUMBER', '51975852932'),

    'store_url' => rtrim((string) env('TANISH_STORE_URL', env('WOOCOMMERCE_URL', 'http://localhost:8080')), '/'),

    'currency' => env('TANISH_CURRENCY', 'PEN'),

    'front' => [
        'products_per_page' => (int) env('TANISH_PRODUCTS_PER_PAGE', 12),
    ],

    'admin' => [
        'username' => env('TANISH_ADMIN_USERNAME', 'tanish_admin'),
        'email' => env('TANISH_ADMIN_EMAIL', 'tanish_admin@tanish.local'),
        'password' => env('TANISH_ADMIN_PASSWORD'),
    ],
];

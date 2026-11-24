<?php

use App\Http\Controllers\Api\WooCommerceWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API del servicio Laravel
|--------------------------------------------------------------------------
|
| El endpoint de webhooks no usa autenticación de sesión: la autenticidad
| se comprueba con la firma HMAC SHA256 de WooCommerce.
|
*/

Route::post('/webhooks/woocommerce', WooCommerceWebhookController::class)
    ->name('webhooks.woocommerce');

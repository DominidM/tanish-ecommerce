<?php

use App\Http\Controllers\QuoteController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tienda pública (Laravel)
|--------------------------------------------------------------------------
|
| Catálogo de solo lectura alimentado por los snapshots de WooCommerce.
| El carrito/checkout real sigue en WooCommerce; aquí se arma una
| cotización que se envía por WhatsApp, el canal comercial de TANISH.
|
*/

Route::get('/', [StorefrontController::class, 'home'])->name('storefront.home');
Route::get('/tienda', [StorefrontController::class, 'catalog'])->name('storefront.catalog');
Route::get('/producto/{identifier}', [StorefrontController::class, 'product'])->name('storefront.product');

Route::get('/cotizacion', [QuoteController::class, 'show'])->name('quote.show');
Route::post('/cotizacion/agregar/{product}', [QuoteController::class, 'add'])->name('quote.add');
Route::post('/cotizacion/quitar/{product}', [QuoteController::class, 'remove'])->name('quote.remove');
Route::post('/cotizacion/limpiar', [QuoteController::class, 'clear'])->name('quote.clear');
Route::get('/cotizacion/whatsapp', [QuoteController::class, 'whatsapp'])->name('quote.whatsapp');

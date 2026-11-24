<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Reconciliación periódica con WooCommerce
|--------------------------------------------------------------------------
|
| Recupera pedidos/productos que no llegaron por webhook y compara el stock
| local con la fuente de verdad. Requiere el scheduler en ejecución.
|
*/
Schedule::command('woo:reconcile --days=7')
    ->hourly()
    ->withoutOverlapping();

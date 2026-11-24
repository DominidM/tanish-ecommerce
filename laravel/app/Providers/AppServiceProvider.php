<?php

namespace App\Providers;

use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            WooCommerceClient::class,
            static fn (): WooCommerceClient => WooCommerceClient::fromConfig()
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

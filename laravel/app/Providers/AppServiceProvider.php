<?php

namespace App\Providers;

use App\Filament\Auth\LoginResponse;
use App\Services\WooCommerce\WooCommerceClient;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\URL;
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

        $this->app->bind(
            LoginResponseContract::class,
            LoginResponse::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Detrás de Caddy/Cloudflare las URLs deben usar el host público
        // configurado en APP_URL (por ejemplo, el Worker HTTPS), no el host
        // interno del origen.
        $appUrl = (string) config('app.url');

        if ($appUrl !== '') {
            URL::forceRootUrl($appUrl);

            $scheme = parse_url($appUrl, PHP_URL_SCHEME);

            if (is_string($scheme) && $scheme !== '') {
                URL::forceScheme($scheme);
            }
        }
    }
}

<?php

namespace App\Providers;

use App\Services\GenieAcs\GenieAcsService;
use App\Services\Payment\TripayService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TripayService::class, function () {
            return new TripayService(
                baseUrl: config('tripay.base_url'),
                merchantCode: (string) config('tripay.merchant_code'),
                apiKey: (string) config('tripay.api_key'),
                privateKey: (string) config('tripay.private_key'),
            );
        });

        $this->app->singleton(GenieAcsService::class, function () {
            return new GenieAcsService(
                baseUrl: config('genieacs.base_url'),
                username: config('genieacs.username'),
                password: config('genieacs.password'),
            );
        });

        $this->app->singleton(WhatsAppService::class, function () {
            return new WhatsAppService(baseUrl: config('whatsapp.engine_url'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super-admin can access every menu/permission unconditionally — checked before any
        // specific ability/permission gate, so per-user menu checklists never need to include it.
        Gate::before(function ($user, string $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        // Requests arrive at php-fpm over plain HTTP (nginx <- cloudflared tunnel),
        // so force https for generated URLs whenever APP_URL says the app is served over https.
        // Also mark the incoming request itself as secure, otherwise signed-URL validation
        // (which reconstructs the request's own URL) sees "http" while generation produced
        // "https", and every signed link 403s with "Invalid signature".
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');

            if ($this->app->bound('request')) {
                $this->app['request']->server->set('HTTPS', 'on');
            }
        }
    }
}

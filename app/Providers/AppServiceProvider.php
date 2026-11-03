<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The real visitor's IP. TrustGatewayHeaders stores it in the "visitor_ip" attribute
        // for requests coming from our own website; direct callers fall back to ip().
        Request::macro('visitorIp', function () {
            return $this->attributes->get('visitor_ip') ?? $this->ip();
        });

        // Login / register: slow down password guessing and mass sign-ups.
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(6)->by(
                $request->visitorIp() . '|' . strtolower((string) $request->input('email'))
            );
        });

        // Public inquiry form: stop spam floods.
        RateLimiter::for('inquiries', function (Request $request) {
            return Limit::perMinute(5)->by($request->visitorIp());
        });
    }
}
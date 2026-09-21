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
        // Login: 5 percobaan/menit per email+IP (mencegah brute force).
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        // Endpoint publik tanpa token: 30/menit per IP.
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));

        // Endpoint lain: 60/menit per user (fallback ke IP bila token tidak valid).
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user('api')?->getKey() ?: $request->ip());
        });
    }
}

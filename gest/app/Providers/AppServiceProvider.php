<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

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
        RateLimiter::for('api-auth', function (Request $request): array {
            $email = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(10)->by('auth-ip:'.$request->ip()),
                Limit::perMinute(5)->by('auth-attempt:'.$request->ip().'|'.$email),
            ];
        });

        RateLimiter::for('magic-link', function (Request $request): array {
            $email = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)->by('magic-link-ip:'.$request->ip()),
                Limit::perHour(3)->by('magic-link-email:'.$email),
            ];
        });

        RateLimiter::for('gym-registration', function (Request $request): array {
            $email = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)->by('gym-registration-ip:'.$request->ip()),
                Limit::perHour(5)->by('gym-registration-email:'.$email),
            ];
        });

        RateLimiter::for('email-verification', function (Request $request): array {
            $email = Str::lower(trim((string) $request->input('email')));
            $emailHash = hash('sha256', $email);

            return [
                Limit::perMinute(15)->by('email-verification-ip:'.$request->ip()),
                Limit::perMinute(5)->by('email-verification-address:'.$emailHash),
            ];
        });

        RateLimiter::for('email-verification-resend', function (Request $request): array {
            $email = Str::lower(trim((string) $request->input('email')));
            $emailHash = hash('sha256', $email);

            return [
                Limit::perMinute(5)->by('email-verification-resend-ip:'.$request->ip()),
                Limit::perMinute(1)->by('email-verification-resend-address-minute:'.$emailHash),
                Limit::perHour(5)->by('email-verification-resend-address-hour:'.$emailHash),
            ];
        });

        Password::defaults(function (): Password {
            $rule = Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols();

            return app()->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });
    }
}

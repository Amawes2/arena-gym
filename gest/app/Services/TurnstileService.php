<?php

namespace App\Services;

use App\Rules\ValidTurnstileToken;
use Illuminate\Support\Facades\Http;
use Throwable;

class TurnstileService
{
    public function isRequired(): bool
    {
        return app()->isProduction()
            || filled(config('services.turnstile.site_key'))
            || filled(config('services.turnstile.secret_key'));
    }

    /**
     * @return array<int, mixed>
     */
    public function validationRules(string $action): array
    {
        if (! $this->isRequired()) {
            return ['nullable', 'string', 'max:2048'];
        }

        return ['required', 'string', 'max:2048', new ValidTurnstileToken($action)];
    }

    public function verify(string $token, ?string $ipAddress, string $action): bool
    {
        $secretKey = config('services.turnstile.secret_key');

        if (! filled($secretKey) || ! filled(config('services.turnstile.site_key'))) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->connectTimeout(3)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array_filter([
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $ipAddress,
                ]));

            if (! $response->successful() || $response->json('success') !== true || $response->json('action') !== $action) {
                return false;
            }

            $expectedHostname = config('services.turnstile.hostname');

            return ! filled($expectedHostname)
                || strtolower((string) $response->json('hostname')) === strtolower((string) $expectedHostname);
        } catch (Throwable) {
            return false;
        }
    }
}

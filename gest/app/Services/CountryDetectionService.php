<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;

class CountryDetectionService
{
    public function detect(Request $request): ?string
    {
        if (! config('services.cloudflare.country_detection_enabled', true)) {
            return null;
        }

        $remoteAddress = $request->server->get('REMOTE_ADDR');

        if (! is_string($remoteAddress) || $remoteAddress === '' || ! $this->isTrustedProxy($remoteAddress)) {
            return null;
        }

        $countryCode = Str::upper(trim((string) $request->header('CF-IPCountry')));

        return array_key_exists($countryCode, config('billing.countries', [])) ? $countryCode : null;
    }

    private function isTrustedProxy(string $remoteAddress): bool
    {
        foreach (config('services.cloudflare.trusted_country_proxy_cidrs', []) as $trustedRange) {
            if (is_string($trustedRange) && IpUtils::checkIp($remoteAddress, $trustedRange)) {
                return true;
            }
        }

        return false;
    }
}

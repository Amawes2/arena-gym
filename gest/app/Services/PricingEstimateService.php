<?php

namespace App\Services;

use App\Models\Gym;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PricingEstimateService
{
    /**
     * @return array{base_amount: int, currency_code: string, local_amount: float|null, rate_date: string|null, rate_source: string, estimate: bool}
     */
    public function forGym(Gym $gym): array
    {
        $currency = strtoupper((string) ($gym->currency_code ?: 'XOF'));
        $baseAmount = (int) config('billing.base_price_xof', 7999);

        if (in_array($currency, ['XOF', 'XAF'], true)) {
            return [
                'base_amount' => $baseAmount,
                'currency_code' => $currency,
                'local_amount' => (float) $baseAmount,
                'rate_date' => null,
                'rate_source' => 'fcfa',
                'estimate' => false,
            ];
        }

        $euroAmount = $baseAmount * (float) config('billing.currency_conversion.base_eur_per_xof', 1 / 655.957);

        if ($currency === 'EUR') {
            return [
                'base_amount' => $baseAmount,
                'currency_code' => $currency,
                'local_amount' => round($euroAmount, 2),
                'rate_date' => null,
                'rate_source' => 'parity',
                'estimate' => true,
            ];
        }

        $rate = $this->euroRate($currency);

        return [
            'base_amount' => $baseAmount,
            'currency_code' => $currency,
            'local_amount' => $rate['rate'] === null ? null : round($euroAmount * $rate['rate'], 2),
            'rate_date' => $rate['date'],
            'rate_source' => $rate['rate'] === null ? 'unavailable' : 'daily_reference',
            'estimate' => $rate['rate'] !== null,
        ];
    }

    /** @return array{rate: float|null, date: string|null} */
    private function euroRate(string $currency): array
    {
        $cacheKey = 'arena-billing-fx:EUR:'.$currency;

        return Cache::remember($cacheKey, now()->addMinutes((int) config('billing.currency_conversion.cache_minutes', 1440)), function () use ($currency): array {
            $url = rtrim((string) config('billing.currency_conversion.api_base_url'), '/').'/rate/eur/'.strtolower($currency);

            try {
                $response = Http::acceptJson()
                    ->connectTimeout(2)
                    ->timeout(5)
                    ->retry(2, 200, throw: false)
                    ->get($url);

                if (! $response->successful() || ! is_numeric($response->json('rate'))) {
                    return ['rate' => null, 'date' => null];
                }

                return [
                    'rate' => (float) $response->json('rate'),
                    'date' => $response->json('date'),
                ];
            } catch (ConnectionException|RequestException) {
                return ['rate' => null, 'date' => null];
            }
        });
    }
}

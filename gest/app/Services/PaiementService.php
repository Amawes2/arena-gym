<?php

namespace App\Services;

/**
 * Intégration future : Wave, Orange Money, MTN, Stripe.
 */
final class PaiementService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{provider: string, reference: string, checkout_url: string|null}
     */
    public function initier(string $provider, float $montant, array $payload): array
    {
        return [
            'provider' => $provider,
            'reference' => 'PAY-'.now()->format('YmdHis'),
            'checkout_url' => null,
        ];
    }
}

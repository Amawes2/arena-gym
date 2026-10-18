<?php

namespace App\Services;

use App\Models\Plan;
use App\Support\EliteFitGym;
use Illuminate\Support\Collection;

final class AbonnementService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listForfaits(): Collection
    {
        $gym = EliteFitGym::resolve();

        if (! $gym->is_active || $gym->expires_at?->lt(today())) {
            return collect();
        }

        return Plan::withoutGlobalScopes()
            ->where('gym_id', $gym->id)
            ->where('is_active', true)
            ->orderBy('price')
            ->get()
            ->map(fn (Plan $plan): array => [
                'id' => $plan->id,
                'nom' => $plan->name,
                'prix' => (float) $plan->price,
                'duree_jours' => $this->durationDays($plan),
                'description' => $plan->description,
                'avantages' => $this->avantagesFor($plan),
                'featured' => str_contains(strtolower($plan->name), 'mensuel'),
            ]);
    }

    private function durationDays(Plan $plan): ?int
    {
        if ($plan->duration_days) {
            return (int) $plan->duration_days;
        }

        return match ($plan->billing_period?->value ?? '') {
            'monthly' => 30,
            'quarterly' => 90,
            'annual' => 365,
            default => (int) ($plan->duration_days ?: 1),
        };
    }

    /**
     * @return list<string>
     */
    private function avantagesFor(Plan $plan): array
    {
        $name = strtolower($plan->name);

        if (str_contains($name, 'séance') || str_contains($name, 'seance')) {
            return [
                'Accès complet à la salle',
                'Sans engagement',
                'Inscription en 30 secondes',
            ];
        }

        if (str_contains($name, 'annuel')) {
            return [
                'Accès illimité 365 jours',
                '4 séances coaching offertes',
                'Bilan physique mensuel',
                '-20% sur la boutique',
            ];
        }

        return [
            'Accès illimité',
            '1 séance coaching offerte',
            '-10% sur la boutique',
        ];
    }
}

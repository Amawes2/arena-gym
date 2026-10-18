<?php

namespace App\Services;

use App\Enums\CheckInType;
use App\Enums\ClientType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class CheckInService
{
    /**
     * Enregistre une entrée pour un abonné avec validation complète.
     *
     * @throws \RuntimeException Si l'abonnement est invalide ou expiré.
     */
    public function recordForSubscriber(Member $member, ?int $subscriptionId = null): CheckIn
    {
        $subscription = $subscriptionId
            ? $member->subscriptions()->whereKey($subscriptionId)->first()
            : $member->subscriptions()->active()->latest('start_date')->first();

        if (! $subscription) {
            throw new \RuntimeException("Aucun abonnement actif trouvé pour {$member->name}.");
        }

        if (! $subscription->isAccessible()) {
            if ($subscription->end_date->lt(today())) {
                throw new \RuntimeException("L'abonnement de {$member->name} a expiré le {$subscription->end_date->format('d/m/Y')}.");
            }

            if ($subscription->sessions_remaining !== null && $subscription->sessions_remaining <= 0) {
                throw new \RuntimeException("Plus de séances disponibles pour {$member->name}. Renouveler l'abonnement.");
            }

            throw new \RuntimeException("Abonnement non accessible pour {$member->name}.");
        }

        return DB::transaction(function () use ($member, $subscription): CheckIn {
            $subscription = $member->subscriptions()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $subscription->isAccessible()) {
                throw new \RuntimeException("Abonnement non accessible pour {$member->name}.");
            }

            $checkIn = CheckIn::create([
                'member_id' => $member->id,
                'subscription_id' => $subscription->id,
                'type' => CheckInType::Subscription,
                'checked_in_at' => now(),
            ]);

            // Décrémentation automatique si forfait à séances limitées
            if ($subscription->sessions_remaining !== null) {
                $newCount = max(0, $subscription->sessions_remaining - 1);
                $subscription->sessions_remaining = $newCount;

                if ($newCount <= 0) {
                    $subscription->status = SubscriptionStatus::Expired;
                }

                $subscription->save();
            }

            return $checkIn;
        });
    }

    /**
     * Enregistre une séance unique (walk-in) avec paiement immédiat.
     *
     * @param  array{last_name: string, first_name: string, phone: string, amount: float|int, payment_method: string}  $data
     */
    public function recordWalkIn(array $data): CheckIn
    {
        return DB::transaction(function () use ($data): CheckIn {
            // Chercher si le membre walk-in existe déjà (par téléphone)
            $member = Member::query()->where('phone', $data['phone'])->first();

            if (! $member) {
                $member = Member::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'client_type' => ClientType::WalkIn,
                ]);
            }

            $checkIn = CheckIn::create([
                'member_id' => $member->id,
                'subscription_id' => null,
                'type' => CheckInType::WalkIn,
                'checked_in_at' => now(),
            ]);

            Payment::create([
                'member_id' => $member->id,
                'subscription_id' => null,
                'amount' => $data['amount'] ?? 0,
                'payment_method' => $data['payment_method'] ?? PaymentMethod::Cash->value,
                'status' => PaymentStatus::Completed,
                'paid_at' => now(),
            ]);

            return $checkIn;
        });
    }
}

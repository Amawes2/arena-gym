<?php

namespace App\Services;

use App\Enums\ClientType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Support\EliteFitGym;
use App\Support\PlanEndDateCalculator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class InscriptionRapideService
{
    /**
     * @param  array{prenom: string, nom: string, telephone: string, mode_paiement?: string}  $data
     */
    public function seanceUnique(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $gym = $this->activeGym();
            $member = $this->upsertMember($data, ClientType::WalkIn, (int) $gym->id);
            $amount = (float) ($gym->walk_in_price ?? config('elitefit.walk_in_price'));

            $payment = Payment::withoutGlobalScopes()->create([
                'gym_id' => $gym->id,
                'member_id' => $member->id,
                'subscription_id' => null,
                'amount' => $amount,
                'payment_method' => $data['mode_paiement'] ?? PaymentMethod::Cash->value,
                'reference' => 'SEANCE-'.now()->format('YmdHis'),
                'status' => PaymentStatus::Pending,
                'paid_at' => null,
            ]);

            return [
                'membre' => $this->formatMembre($member),
                'presence' => null,
                'paiement' => [
                    'id' => $payment->id,
                    'montant' => $amount,
                    'reference' => $payment->reference,
                    'statut' => $payment->status->value,
                ],
            ];
        });
    }

    /**
     * @param  array{prenom: string, nom: string, telephone: string, forfait_id: int, mode_paiement?: string}  $data
     */
    public function abonnement(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $gym = $this->activeGym();
            $plan = Plan::withoutGlobalScopes()
                ->where('gym_id', $gym->id)
                ->where('is_active', true)
                ->findOrFail($data['forfait_id']);

            $member = $this->upsertMember($data, ClientType::Subscriber, (int) $gym->id);
            $startDate = now()->toDateString();
            $endDate = PlanEndDateCalculator::endDateFor($plan, $startDate)->toDateString();

            $subscription = Subscription::withoutGlobalScopes()->create([
                'gym_id' => $gym->id,
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'sessions_remaining' => $plan->sessions_limit,
                'status' => SubscriptionStatus::Pending,
            ]);

            $payment = Payment::withoutGlobalScopes()->create([
                'gym_id' => $gym->id,
                'member_id' => $member->id,
                'subscription_id' => $subscription->id,
                'amount' => $plan->price,
                'payment_method' => $data['mode_paiement'] ?? PaymentMethod::Cash->value,
                'reference' => 'ABO-'.now()->format('YmdHis'),
                'status' => PaymentStatus::Pending,
                'paid_at' => null,
            ]);

            return [
                'membre' => $this->formatMembre($member),
                'abonnement' => [
                    'id' => $subscription->id,
                    'date_debut' => $startDate,
                    'date_fin' => $endDate,
                    'statut' => $subscription->status->value,
                    'forfait' => $plan->name,
                    'montant' => (float) $plan->price,
                ],
                'paiement' => ['id' => $payment->id, 'reference' => $payment->reference],
            ];
        });
    }

    /**
     * @param  array{prenom: string, nom: string, telephone: string}  $data
     */
    private function upsertMember(array $data, ClientType $type, int $gymId): Member
    {
        $phone = $this->normalizePhone($data['telephone']);

        $member = Member::withoutGlobalScopes()
            ->where('gym_id', $gymId)
            ->where('phone', $phone)
            ->first();

        if ($member) {
            $member->update([
                'first_name' => $data['prenom'],
                'last_name' => $data['nom'],
                'client_type' => $type,
            ]);

            return $member->fresh();
        }

        return Member::withoutGlobalScopes()->create([
            'gym_id' => $gymId,
            'first_name' => $data['prenom'],
            'last_name' => $data['nom'],
            'phone' => $phone,
            'client_type' => $type,
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', trim($phone)) ?? $phone;
    }

    private function activeGym(): Gym
    {
        $gym = EliteFitGym::resolve();

        if (! $gym->is_active || ($gym->expires_at && $gym->expires_at->lt(today()))) {
            throw new HttpException(403, 'Les inscriptions sont suspendues pour cette salle.');
        }

        return $gym;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMembre(Member $member): array
    {
        return [
            'id' => $member->id,
            'prenom' => $member->first_name,
            'nom' => $member->last_name,
            'telephone' => $member->phone,
            'type' => $member->client_type->value,
        ];
    }
}

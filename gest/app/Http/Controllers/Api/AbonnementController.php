<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\AbonnementService;
use App\Support\CurrentGymResolver;
use App\Support\PlanEndDateCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AbonnementController extends Controller
{
    public function forfaits(AbonnementService $service): JsonResponse
    {
        return response()->json(['data' => $service->listForfaits()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'integer'],
            'forfait_id' => ['required', 'integer'],
            'date_debut' => ['nullable', 'date'],
            'mode_paiement' => ['nullable', Rule::enum(PaymentMethod::class)],
        ]);

        $subscription = $this->createOrRenewSubscription(
            memberId: (int) $data['member_id'],
            planId: (int) $data['forfait_id'],
            startDate: $data['date_debut'] ?? now()->toDateString(),
            paymentMethod: $data['mode_paiement'] ?? PaymentMethod::Cash->value,
        );

        return response()->json(['data' => $this->formatSubscription($subscription)], 201);
    }

    public function renouveler(Request $request, Subscription $subscription): JsonResponse
    {
        $this->ensureGym($subscription);
        $data = $request->validate([
            'forfait_id' => ['nullable', 'integer'],
            'date_debut' => ['nullable', 'date'],
            'mode_paiement' => ['nullable', Rule::enum(PaymentMethod::class)],
        ]);

        $newSubscription = $this->createOrRenewSubscription(
            memberId: $subscription->member_id,
            planId: (int) ($data['forfait_id'] ?? $subscription->plan_id),
            startDate: $data['date_debut'] ?? now()->toDateString(),
            paymentMethod: $data['mode_paiement'] ?? PaymentMethod::Cash->value,
        );

        return response()->json(['data' => $this->formatSubscription($newSubscription)]);
    }

    private function createOrRenewSubscription(int $memberId, int $planId, string $startDate, string $paymentMethod): Subscription
    {
        return DB::transaction(function () use ($memberId, $planId, $startDate, $paymentMethod): Subscription {
            $gymId = CurrentGymResolver::id();

            $member = Member::withoutGlobalScopes()
                ->where('gym_id', $gymId)
                ->findOrFail($memberId);

            $plan = Plan::withoutGlobalScopes()
                ->where('gym_id', $gymId)
                ->where('is_active', true)
                ->findOrFail($planId);

            Subscription::withoutGlobalScopes()
                ->where('gym_id', $gymId)
                ->where('member_id', $member->id)
                ->where('status', SubscriptionStatus::Active->value)
                ->update(['status' => SubscriptionStatus::Expired->value]);

            $subscription = Subscription::withoutGlobalScopes()->create([
                'gym_id' => $gymId,
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'start_date' => $startDate,
                'end_date' => PlanEndDateCalculator::endDateFor($plan, $startDate)->toDateString(),
                'sessions_remaining' => $plan->sessions_limit,
                'status' => SubscriptionStatus::Active,
            ]);

            Payment::withoutGlobalScopes()->create([
                'gym_id' => $gymId,
                'member_id' => $member->id,
                'subscription_id' => $subscription->id,
                'amount' => $plan->price,
                'payment_method' => $paymentMethod,
                'reference' => 'ABO-'.now()->format('YmdHis'),
                'status' => PaymentStatus::Completed,
                'paid_at' => now(),
            ]);

            return $subscription->load(['member', 'plan']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSubscription(Subscription $subscription): array
    {
        $subscription->loadMissing(['member', 'plan']);

        return [
            'id' => $subscription->id,
            'membre' => [
                'id' => $subscription->member->id,
                'prenom' => $subscription->member->first_name,
                'nom' => $subscription->member->last_name,
                'telephone' => $subscription->member->phone,
            ],
            'forfait' => [
                'id' => $subscription->plan->id,
                'nom' => $subscription->plan->name,
                'prix' => (float) $subscription->plan->price,
            ],
            'date_debut' => $subscription->start_date?->toDateString(),
            'date_fin' => $subscription->end_date?->toDateString(),
            'statut' => $subscription->status->value,
        ];
    }

    private function ensureGym(Subscription $subscription): void
    {
        if ((int) $subscription->gym_id !== CurrentGymResolver::id()) {
            abort(404);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\CheckInType;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Member;
use App\Models\Subscription;
use App\Support\CurrentGymResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PresenceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'integer'],
            'subscription_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:190'],
        ]);

        $checkIn = DB::transaction(function () use ($data): CheckIn {
            $gymId = CurrentGymResolver::id();

            $member = Member::withoutGlobalScopes()
                ->where('gym_id', $gymId)
                ->lockForUpdate()
                ->findOrFail($data['member_id']);

            $subscription = isset($data['subscription_id'])
                ? Subscription::withoutGlobalScopes()
                    ->where('gym_id', $gymId)
                    ->where('member_id', $member->id)
                    ->lockForUpdate()
                    ->findOrFail($data['subscription_id'])
                : $member->subscriptions()
                    ->withoutGlobalScopes()
                    ->where('gym_id', $gymId)
                    ->lockForUpdate()
                    ->active()
                    ->latest('start_date')
                    ->first();

            if ($subscription && ! $subscription->isAccessible()) {
                abort(422, 'Abonnement expiré ou non accessible.');
            }

            $checkIn = CheckIn::withoutGlobalScopes()->create([
                'gym_id' => $gymId,
                'member_id' => $member->id,
                'subscription_id' => $subscription?->id,
                'type' => $subscription ? CheckInType::Subscription : CheckInType::WalkIn,
                'checked_in_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            if ($subscription && $subscription->sessions_remaining !== null) {
                $subscription->sessions_remaining = max(0, $subscription->sessions_remaining - 1);

                if ($subscription->sessions_remaining <= 0) {
                    $subscription->status = SubscriptionStatus::Expired;
                }

                $subscription->save();
            }

            return $checkIn->load(['member', 'subscription.plan']);
        });

        return response()->json([
            'message' => 'Présence enregistrée.',
            'data' => [
                'id' => $checkIn->id,
                'date_heure' => $checkIn->checked_in_at?->toIso8601String(),
                'type' => $checkIn->type->value,
                'membre' => [
                    'id' => $checkIn->member->id,
                    'prenom' => $checkIn->member->first_name,
                    'nom' => $checkIn->member->last_name,
                    'telephone' => $checkIn->member->phone,
                ],
                'forfait' => $checkIn->subscription?->plan?->name,
            ],
        ], 201);
    }
}

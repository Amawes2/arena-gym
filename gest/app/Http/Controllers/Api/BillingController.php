<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\SupportMessage;
use App\Models\User;
use App\Services\CountryDetectionService;
use App\Services\PricingEstimateService;
use App\Support\CurrentGymResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function countries(Request $request, CountryDetectionService $countryDetection): JsonResponse
    {
        $countries = collect(config('billing.countries', []))
            ->map(fn (array $country, string $code): array => [
                'code' => $code,
                'name' => $country['name'],
                'currency_code' => $country['currency'],
            ])
            ->values();

        return response()->json([
            'data' => $countries,
            'detected_country_code' => $countryDetection->detect($request),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function status(Request $request, PricingEstimateService $pricing): JsonResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User && ! $user->isSuperAdmin(), 403);

        $gym = Gym::query()->findOrFail(CurrentGymResolver::id());
        $limit = $gym->memberSubscriptionCreationLimit();

        return response()->json([
            'gym' => [
                'id' => $gym->id,
                'name' => $gym->name,
                'is_approved' => $gym->is_approved,
                'is_active' => $gym->is_active,
                'expires_at' => $gym->expires_at?->toDateString(),
                'is_read_only' => $gym->isSaasReadOnly(),
                'subscriptions_created' => $limit === null ? null : $gym->provisionalSubscriptionCount(),
                'subscriptions_limit' => $limit,
            ],
            'price' => $pricing->forGym($gym),
            'manual_payment_currency' => 'XOF',
        ]);
    }

    public function requestRenewal(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User && in_array($user->roleEnum(), [UserRole::Admin, UserRole::Receptionist], true), 403);

        $gym = Gym::query()->findOrFail(CurrentGymResolver::id());
        $existingRequest = SupportMessage::query()
            ->where('gym_id', $gym->id)
            ->where('category', 'renewal')
            ->where('sender_role', '!=', UserRole::SuperAdmin->value)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->exists();

        if ($existingRequest) {
            return response()->json(['message' => 'Votre demande de renouvellement est déjà transmise au Super Admin.'], 202);
        }

        SupportMessage::query()->create([
            'gym_id' => $gym->id,
            'user_id' => $user->id,
            'sender_role' => $user->roleEnum()?->value ?? UserRole::Admin->value,
            'category' => 'renewal',
            'body' => 'Demande de renouvellement pour la salle « '.$gym->name.' ». Tarif de référence : 7 999 FCFA. Paiement manuel en FCFA. Merci de transmettre les instructions de règlement.',
        ]);

        return response()->json([
            'message' => 'Demande envoyée au Super Admin. Le renouvellement sera activé après confirmation du paiement manuel en FCFA.',
        ], 201);
    }

    public function completeOnboarding(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User && in_array($user->roleEnum(), [UserRole::Admin, UserRole::Receptionist], true), 403);

        $user->forceFill(['onboarding_completed_at' => now()])->save();

        return response()->json(['message' => 'Guide terminé.']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\GymRegistrationRequest;
use App\Http\Requests\InscriptionRapideRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\MemberRegistrationRequest;
use App\Models\Gym;
use App\Models\User;
use App\Services\InscriptionRapideService;
use App\Services\PendingRegistrationService;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AuthController extends Controller
{
    public function registerRapide(InscriptionRapideRequest $request, InscriptionRapideService $service): JsonResponse
    {
        $data = $request->validated();

        $result = $data['type'] === 'abonnement'
            ? $service->abonnement($data)
            : $service->seanceUnique($data);

        return response()->json([
            'message' => 'Demande enregistrée. Le paiement doit être confirmé par la salle avant activation.',
            'data' => $result,
        ], 201);
    }

    public function abonnement(InscriptionRapideRequest $request, InscriptionRapideService $service): JsonResponse
    {
        $data = array_merge($request->validated(), ['type' => 'abonnement']);

        return response()->json([
            'message' => 'Demande enregistrée. Le paiement doit être confirmé par la salle avant activation.',
            'data' => $service->abonnement($data),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($credentials['email']))])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        $panel = $user->isSuperAdmin() ? Filament::getPanel('platform') : Filament::getPanel('admin');

        if (! $user->canAccessPanel($panel)) {
            throw ValidationException::withMessages([
                'email' => ['Ce compte ne dispose pas d’un accès administrateur.'],
            ]);
        }

        $tenant = $user->isSuperAdmin() ? null : $user->getDefaultTenant($panel);

        if (! $user->isSuperAdmin() && ! $tenant) {
            throw ValidationException::withMessages([
                'email' => ['Aucune salle n’est associée à ce compte.'],
            ]);
        }

        if (! $user->isSuperAdmin() && $user->role === UserRole::Admin && ! $user->email_verified_at) {
            return response()->json([
                'message' => 'Confirmez votre adresse e-mail avant de vous connecter.',
                'email' => $user->email,
                'verification_required' => true,
            ], 403);
        }

        if (filled($user->getAppAuthenticationSecret())) {
            Filament::setCurrentPanel($panel);
            $code = trim((string) ($credentials['two_factor_code'] ?? ''));
            $authenticator = AppAuthentication::make();
            $isValidCode = preg_match('/^\d{6}$/', $code) === 1
                && $authenticator->verifyCode($code, $user->getAppAuthenticationSecret(), shouldPreventCodeReuse: true);
            $isValidRecoveryCode = ! $isValidCode
                && preg_match('/^[A-Za-z0-9]{10}-[A-Za-z0-9]{10}$/', $code) === 1
                && $authenticator->verifyRecoveryCode($code, $user);

            if (! $isValidCode && ! $isValidRecoveryCode) {
                throw ValidationException::withMessages([
                    'two_factor_code' => ['Le code de double authentification est invalide ou déjà utilisé.'],
                ]);
            }
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return response()->json([
            'redirect_url' => $user->isSuperAdmin() ? $panel->getUrl() : url('/admin'),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value ?? UserRole::Admin->value,
            ],
        ])->header('Cache-Control', 'no-store, private');
    }

    public function register(MemberRegistrationRequest $request, PendingRegistrationService $pendingRegistrationService): JsonResponse
    {
        $data = $request->validated();
        unset($data['turnstile_token'], $data['website']);

        try {
            $pendingRegistration = $pendingRegistrationService->begin($data, UserRole::Membre);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Votre inscription temporaire est enregistrée, mais l’OTP n’a pas pu être envoyé. Demandez un nouvel envoi.',
                'email' => strtolower(trim($data['email'])),
                'verification_pending' => true,
            ], 503);
        }

        return response()->json([
            'message' => 'Un code de vérification vient d’être envoyé à votre adresse e-mail.',
            'email' => $pendingRegistration->email,
        ], 202);
    }

    public function registerGym(GymRegistrationRequest $request, PendingRegistrationService $pendingRegistrationService): JsonResponse
    {
        $data = $request->validated();
        unset($data['turnstile_token'], $data['website']);

        try {
            $pendingRegistration = $pendingRegistrationService->begin($data, UserRole::Admin);
        } catch (RuntimeException $exception) {
            report($exception);

            return response()->json([
                'message' => 'Votre inscription temporaire est enregistrée, mais l’OTP n’a pas pu être envoyé. Demandez un nouvel envoi.',
                'email' => strtolower(trim($data['email'])),
                'verification_pending' => true,
            ], 503);
        }

        return response()->json([
            'message' => 'Un code de vérification vient d’être envoyé à votre adresse e-mail.',
            'email' => $pendingRegistration->email,
        ], 202);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $gyms = collect($user->getTenants(Filament::getPanel('admin')))
            ->map(fn (Gym $gym): array => [
                'id' => $gym->id,
                'name' => $gym->name,
                'slug' => $gym->slug,
            ])
            ->values();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value,
                'onboarding_completed_at' => $user->onboarding_completed_at?->toIso8601String(),
            ],
            'gyms' => $gyms,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }
}

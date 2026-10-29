<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Services\PendingRegistrationService;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class EmailVerificationController extends Controller
{
    public function verify(
        Request $request,
        EmailVerificationService $verificationService,
        PendingRegistrationService $pendingRegistrationService,
    ): JsonResponse {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = Str::lower(trim($data['email']));
        $pendingRegistration = PendingRegistration::query()
            ->where('email', $email)
            ->where('role', UserRole::Membre->value)
            ->where('registration_expires_at', '>', now())
            ->first();

        if ($pendingRegistration) {
            $user = $pendingRegistrationService->verifyAndCreateUser($email, $data['code']);

            if (! $user) {
                try {
                    $pendingRegistrationService->resendAfterInvalidAttempt($email);
                } catch (RuntimeException $exception) {
                    report($exception);
                }

                return response()->json([
                    'message' => 'Le code est invalide ou expiré. Si une inscription est en cours, un nouvel OTP a été envoyé si le délai le permet.',
                    'errors' => [
                        'code' => ['Le code est invalide ou expiré.'],
                    ],
                ], 422);
            }

            return $this->authenticateAndRespond($user, $request);
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereIn('role', [UserRole::Membre->value, UserRole::Admin->value])
            ->first();

        if (! $user || $user->email_verified_at || ! $verificationService->verifyCode($user, $data['code'])) {
            throw ValidationException::withMessages([
                'code' => ['Le code est invalide ou expiré. Demandez un nouveau code puis réessayez.'],
            ]);
        }

        return $this->authenticateAndRespond($user, $request);
    }

    public function resend(
        Request $request,
        EmailVerificationService $verificationService,
        PendingRegistrationService $pendingRegistrationService,
    ): JsonResponse {
        $data = $request->validate(['email' => ['required', 'email', 'max:254']]);
        $email = Str::lower(trim($data['email']));
        $pendingRegistration = PendingRegistration::query()
            ->where('email', $email)
            ->where('role', UserRole::Membre->value)
            ->where('registration_expires_at', '>', now())
            ->first();

        $user = $pendingRegistration ? null : User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereIn('role', [UserRole::Membre->value, UserRole::Admin->value])
            ->whereNull('email_verified_at')
            ->first();

        if ($pendingRegistration || $user) {
            try {
                if ($pendingRegistration) {
                    $pendingRegistrationService->resendPendingCode($email);
                } else {
                    $verificationService->sendCode($user);
                }
            } catch (RuntimeException $exception) {
                report($exception);

                return response()->json([
                    'message' => 'Le service e-mail est momentanément indisponible. Réessayez plus tard.',
                ], 503);
            }
        }

        return response()->json([
            'message' => 'Si un compte non vérifié correspond à cette adresse, un nouveau code vient d’être envoyé.',
        ], 202);
    }

    private function authenticateAndRespond(User $user, Request $request): JsonResponse
    {
        if ($user->role === UserRole::Admin && filled($user->getAppAuthenticationSecret())) {
            return response()->json([
                'message' => 'Adresse vérifiée. Connectez-vous avec votre mot de passe et votre code de double authentification.',
                'redirect_url' => '/connexion',
            ])->header('Cache-Control', 'no-store, private');
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $redirectUrl = '/';

        if ($user->role === UserRole::Admin) {
            $panel = Filament::getPanel('admin');
            $tenant = $user->getDefaultTenant($panel);

            if ($tenant && $user->canAccessPanel($panel)) {
                $redirectUrl = url('/admin');
            }
        }

        return response()->json([
            'redirect_url' => $redirectUrl,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value,
            ],
        ])->header('Cache-Control', 'no-store, private');
    }
}

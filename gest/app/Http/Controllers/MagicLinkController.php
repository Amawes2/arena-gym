<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\MagicLinkRequest;
use App\Mail\MagicLoginLink;
use App\Models\User;
use App\Services\ResendEmailService;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class MagicLinkController extends Controller
{
    public function requestLink(MagicLinkRequest $request, ResendEmailService $emailService): JsonResponse
    {
        $email = Str::lower(trim($request->validated('email')));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user && $this->canUseMagicLink($user)) {
            $token = Str::random(64);
            $expiresAt = now()->addMinutes(10);

            DB::table('magic_login_tokens')->where('user_id', $user->id)->delete();
            DB::table('magic_login_tokens')->insert([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $url = URL::temporarySignedRoute('auth.magic.confirm', $expiresAt, ['token' => $token]);
            $mail = new MagicLoginLink($url, $expiresAt->format('d/m/Y à H:i'));

            try {
                $emailService->send($user->email, $mail->envelope()->subject, $mail->render());
            } catch (Throwable $exception) {
                DB::table('magic_login_tokens')->where('token_hash', hash('sha256', $token))->delete();
                Log::warning('Magic link email dispatch failed.', ['exception' => $exception::class]);
            }
        }

        return response()->json([
            'message' => 'Si un compte administrateur correspond à cette adresse, un lien de connexion vient d’être envoyé.',
        ], 202);
    }

    public function confirm(Request $request, string $token)
    {
        $tokenRecord = DB::table('magic_login_tokens')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        $user = $tokenRecord ? User::query()->find($tokenRecord->user_id) : null;

        if (! $user || ! $this->canUseMagicLink($user)) {
            return redirect('/connexion?magic=invalid');
        }

        return response()
            ->view('auth.magic-link-confirm', [
                'consumeUrl' => $request->fullUrl(),
                'requiresTwoFactor' => filled($user->getAppAuthenticationSecret()),
            ])
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function consume(Request $request, string $token)
    {
        $twoFactorCode = trim((string) $request->input('two_factor_code'));

        $user = DB::transaction(function () use ($token, $twoFactorCode): ?User {
            $tokenRecord = DB::table('magic_login_tokens')
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            if (! $tokenRecord || $tokenRecord->used_at !== null || now()->greaterThanOrEqualTo($tokenRecord->expires_at)) {
                return null;
            }

            $account = User::query()->find($tokenRecord->user_id);
            $isEligible = $account && $this->canUseMagicLink($account);

            if (! $isEligible || ! $this->verifySecondFactor($account, $twoFactorCode)) {
                return null;
            }

            $updated = DB::table('magic_login_tokens')
                ->where('id', $tokenRecord->id)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->update(['used_at' => now(), 'updated_at' => now()]);

            return $updated === 1 ? $account : null;
        });

        if (! $user) {
            return redirect('/connexion?magic=invalid');
        }

        Filament::setCurrentPanel($user->isSuperAdmin() ? 'platform' : 'admin');
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        if ($user->isSuperAdmin()) {
            return redirect(Filament::getPanel('platform')->getUrl());
        }

        return redirect('/admin');
    }

    private function canUseMagicLink(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! in_array($user->roleEnum(), [UserRole::Admin, UserRole::Receptionist], true)) {
            return false;
        }

        if ($user->roleEnum() === UserRole::Admin && ! $user->email_verified_at) {
            return false;
        }

        return $user->canAccessPanel(Filament::getPanel('admin'));
    }

    private function verifySecondFactor(User $user, string $code): bool
    {
        $secret = $user->getAppAuthenticationSecret();

        if (blank($secret)) {
            return true;
        }

        Filament::setCurrentPanel($user->isSuperAdmin() ? 'platform' : 'admin');

        $authenticator = AppAuthentication::make();

        if (preg_match('/^\d{6}$/', $code) === 1) {
            return $authenticator->verifyCode($code, $secret, shouldPreventCodeReuse: true);
        }

        return preg_match('/^[A-Za-z0-9]{10}-[A-Za-z0-9]{10}$/', $code) === 1
            && $authenticator->verifyRecoveryCode($code, $user);
    }
}

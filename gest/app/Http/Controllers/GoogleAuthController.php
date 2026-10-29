<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            return redirect('/connexion?google_error=not_configured');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        if (request()->filled('error')) {
            return redirect('/connexion?google_error=cancelled');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $exception) {
            Log::notice('Google sign-in state validation failed.');

            return redirect('/connexion?google_error=failed');
        }

        $email = strtolower(trim((string) $googleUser->getEmail()));
        $emailIsVerified = filter_var(
            $googleUser->user['email_verified'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );

        if ($email === '' || ! $emailIsVerified) {
            return redirect('/connexion?google_error=unverified');
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user || ! $this->canUseGoogleSignIn($user)) {
            return redirect('/connexion?google_error=no_account');
        }

        if (filled($user->getAppAuthenticationSecret())) {
            return redirect('/connexion?google_error=two_factor');
        }

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::guard('web')->login($user);
        request()->session()->regenerate();

        if ($user->isSuperAdmin()) {
            Filament::setCurrentPanel('platform');

            return redirect(Filament::getPanel('platform')->getUrl());
        }

        Filament::setCurrentPanel('admin');

        return redirect('/admin');
    }

    private function canUseGoogleSignIn(User $user): bool
    {
        if (! in_array($user->roleEnum(), [UserRole::Admin, UserRole::Receptionist, UserRole::SuperAdmin], true)) {
            return false;
        }

        $panel = Filament::getPanel($user->isSuperAdmin() ? 'platform' : 'admin');

        if (! $user->canAccessPanel($panel)) {
            return false;
        }

        return $user->isSuperAdmin() || $user->getDefaultTenant($panel) !== null;
    }
}

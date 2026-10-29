<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFilamentPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Filament::auth();

        if (! $guard->check()) {
            return redirect()->to(Filament::getLoginUrl());
        }

        $user = $guard->user();
        $panel = Filament::getCurrentOrDefaultPanel();

        if (($user instanceof FilamentUser) && (! $user->canAccessPanel($panel))) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to(Filament::getLoginUrl());
        }

        if (! ($user instanceof FilamentUser) && (! app()->isLocal())) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to(Filament::getLoginUrl());
        }

        if ($user->roleEnum() === UserRole::Admin
            && ! $user->email_verified_at
            && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return response()->json([
                'message' => 'Confirmez votre adresse e-mail pour activer les modifications de votre espace.',
                'verification_required' => true,
            ], 403);
        }

        return $next($request);
    }
}

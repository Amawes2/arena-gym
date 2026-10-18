<?php

namespace App\Http\Middleware;

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

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\GymRegistrationRequest;
use App\Services\GymRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GymRegistrationController extends Controller
{
    public function store(GymRegistrationRequest $request, GymRegistrationService $gymRegistrationService): JsonResponse
    {
        $data = $request->validated();
        unset($data['turnstile_token'], $data['website']);

        $registration = $gymRegistrationService->registerGymOwner($data);
        Auth::guard('web')->login($registration['user']);
        $request->session()->regenerate();

        return response()->json([
            'message' => $registration['verification_email_sent']
                ? 'Votre espace est créé. Vous pouvez le consulter immédiatement; vérifiez votre e-mail pour activer les modifications.'
                : 'Votre espace est créé. Le service e-mail est momentanément indisponible; vous pourrez réessayer depuis votre espace.',
            'email' => $registration['user']->email,
            'verification_required' => true,
            'verification_email_sent' => $registration['verification_email_sent'],
            'redirect_url' => url('/admin'),
            'user' => [
                'id' => $registration['user']->id,
                'name' => $registration['user']->name,
                'email' => $registration['user']->email,
                'role' => UserRole::Admin->value,
            ],
        ], 201)->header('Cache-Control', 'no-store, private');
    }
}

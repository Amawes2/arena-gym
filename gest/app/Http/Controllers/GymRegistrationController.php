<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\GymRegistrationRequest;
use App\Services\PendingRegistrationService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class GymRegistrationController extends Controller
{
    public function store(GymRegistrationRequest $request, PendingRegistrationService $pendingRegistrationService): JsonResponse
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
}

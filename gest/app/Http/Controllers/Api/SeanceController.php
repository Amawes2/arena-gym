<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeanceReservationRequest;
use App\Models\CheckIn;
use App\Services\InscriptionRapideService;
use App\Support\CurrentGymResolver;
use Illuminate\Http\JsonResponse;

class SeanceController extends Controller
{
    public function index(): JsonResponse
    {
        $seances = CheckIn::withoutGlobalScopes()
            ->where('gym_id', CurrentGymResolver::id())
            ->with(['member', 'subscription.plan'])
            ->latest('checked_in_at')
            ->paginate(20);

        return response()->json($seances->through(fn (CheckIn $checkIn): array => $this->format($checkIn)));
    }

    public function reserver(SeanceReservationRequest $request, InscriptionRapideService $service): JsonResponse
    {
        $data = $request->validated();

        return response()->json([
            'message' => 'Demande enregistree. Le paiement et la presence seront confirmes par la salle.',
            'data' => $service->seanceUnique($data),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function format(CheckIn $checkIn): array
    {
        return [
            'id' => $checkIn->id,
            'date_heure' => $checkIn->checked_in_at?->toIso8601String(),
            'type' => $checkIn->type->value,
            'statut' => 'presente',
            'membre' => [
                'id' => $checkIn->member?->id,
                'prenom' => $checkIn->member?->first_name,
                'nom' => $checkIn->member?->last_name,
                'telephone' => $checkIn->member?->phone,
            ],
            'forfait' => $checkIn->subscription?->plan?->name,
        ];
    }
}

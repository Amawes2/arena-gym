<?php

namespace App\Http\Controllers\Api;

use App\Enums\ClientType;
use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Support\CurrentGymResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('q');

        $membres = Member::withoutGlobalScopes()
            ->where('gym_id', CurrentGymResolver::id())
            ->when($search->isNotEmpty(), function ($query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(function ($nested) use ($like): void {
                    $nested->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->latest()
            ->paginate(20);

        return response()->json($membres->through(fn (Member $m): array => [
            ...$this->format($m),
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalizeTelephone($request);
        $data = $request->validate([
            'prenom' => ['required', 'string', 'max:50'],
            'nom' => ['required', 'string', 'max:50'],
            'telephone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'email' => ['nullable', 'email'],
            'type' => ['nullable', 'in:subscriber,walk_in'],
        ]);

        $membre = Member::withoutGlobalScopes()->create([
            'gym_id' => CurrentGymResolver::id(),
            'first_name' => $data['prenom'],
            'last_name' => $data['nom'],
            'phone' => preg_replace('/\s+/', '', $data['telephone']),
            'email' => filled($data['email'] ?? null) ? strtolower(trim($data['email'])) : null,
            'client_type' => $data['type'] ?? ClientType::WalkIn->value,
        ]);

        return response()->json(['data' => $this->format($membre)], 201);
    }

    public function show(Member $membre): JsonResponse
    {
        $this->ensureGym($membre);

        return response()->json(['data' => $this->format($membre)]);
    }

    public function update(Request $request, Member $membre): JsonResponse
    {
        $this->ensureGym($membre);
        $this->normalizeTelephone($request);

        $data = $request->validate([
            'prenom' => ['sometimes', 'string', 'max:50'],
            'nom' => ['sometimes', 'string', 'max:50'],
            'telephone' => ['sometimes', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'email' => ['nullable', 'email'],
            'type' => ['nullable', 'in:subscriber,walk_in'],
        ]);

        $membre->update([
            'first_name' => $data['prenom'] ?? $membre->first_name,
            'last_name' => $data['nom'] ?? $membre->last_name,
            'phone' => isset($data['telephone']) ? preg_replace('/\s+/', '', $data['telephone']) : $membre->phone,
            'email' => array_key_exists('email', $data)
                ? (filled($data['email']) ? strtolower(trim($data['email'])) : null)
                : $membre->email,
            'client_type' => $data['type'] ?? $membre->client_type,
        ]);

        return response()->json(['data' => $this->format($membre->fresh())]);
    }

    public function destroy(Member $membre): JsonResponse
    {
        $this->ensureGym($membre);
        $membre->delete();

        return response()->json(['message' => 'Membre supprimé.']);
    }

    private function ensureGym(Member $membre): void
    {
        if ((int) $membre->gym_id !== CurrentGymResolver::id()) {
            abort(404);
        }
    }

    private function normalizeTelephone(Request $request): void
    {
        $telephone = $request->input('telephone');

        if (is_string($telephone)) {
            $request->merge(['telephone' => preg_replace('/\s+/', '', $telephone)]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function format(Member $membre): array
    {
        return [
            'id' => $membre->id,
            'prenom' => $membre->first_name,
            'nom' => $membre->last_name,
            'telephone' => $membre->phone,
            'email' => $membre->email,
            'type' => $membre->client_type->value,
            'created_at' => $membre->created_at?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\CommandeStatut;
use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Produit;
use App\Support\CurrentGymResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommandeController extends Controller
{
    public function index(): JsonResponse
    {
        $commandes = Commande::query()
            ->where('gym_id', CurrentGymResolver::id())
            ->with('lignes')
            ->latest()
            ->paginate(20);

        return response()->json($commandes->through(fn (Commande $c): array => $this->format($c)));
    }

    public function store(Request $request): JsonResponse
    {
        $gymId = CurrentGymResolver::id();
        $data = $request->validate([
            'nom_client' => ['required', 'string', 'max:120'],
            'telephone_client' => ['required', 'string', 'regex:/^[0-9+\s]{8,15}$/'],
            'mode_paiement' => ['nullable', 'string'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.produit_id' => ['required', 'integer'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1'],
        ]);

        $commande = DB::transaction(function () use ($data, $gymId): Commande {
            $total = 0;
            $lignesData = [];

            foreach ($data['lignes'] as $ligne) {
                $produit = Produit::query()
                    ->where('gym_id', $gymId)
                    ->lockForUpdate()
                    ->findOrFail($ligne['produit_id']);

                if ($produit->stock < $ligne['quantite']) {
                    abort(422, "Stock insuffisant pour {$produit->nom}.");
                }

                $sousTotal = (float) $produit->prix * $ligne['quantite'];
                $total += $sousTotal;

                $lignesData[] = [
                    'produit' => $produit,
                    'quantite' => $ligne['quantite'],
                    'sous_total' => $sousTotal,
                ];
            }

            $commande = Commande::query()->create([
                'gym_id' => $gymId,
                'numero' => 'CMD-'.strtoupper(Str::random(8)),
                'nom_client' => $data['nom_client'],
                'telephone_client' => preg_replace('/\s+/', '', $data['telephone_client']),
                'montant_total' => $total,
                'statut' => CommandeStatut::EnAttente,
                'mode_paiement' => $data['mode_paiement'] ?? 'especes',
            ]);

            foreach ($lignesData as $item) {
                $produit = $item['produit'];
                $commande->lignes()->create([
                    'produit_id' => $produit->id,
                    'nom_produit' => $produit->nom,
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $produit->prix,
                    'sous_total' => $item['sous_total'],
                ]);
                $produit->decrement('stock', $item['quantite']);
            }

            return $commande->load('lignes');
        });

        return response()->json(['data' => $this->format($commande)], 201);
    }

    public function updateStatut(Request $request, Commande $commande): JsonResponse
    {
        if ((int) $commande->gym_id !== CurrentGymResolver::id()) {
            abort(404);
        }

        $data = $request->validate([
            'statut' => ['required', 'in:en_attente,confirmee,en_cours,livree,annulee'],
        ]);

        $commande->update(['statut' => $data['statut']]);

        return response()->json(['data' => $this->format($commande->fresh('lignes'))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function format(Commande $commande): array
    {
        return [
            'id' => $commande->id,
            'numero' => $commande->numero,
            'nom_client' => $commande->nom_client,
            'telephone_client' => $commande->telephone_client,
            'montant_total' => (float) $commande->montant_total,
            'statut' => $commande->statut->value,
            'statut_label' => $commande->statut->label(),
            'lignes' => $commande->lignes->map(fn ($l) => [
                'nom' => $l->nom_produit,
                'quantite' => $l->quantite,
                'prix_unitaire' => (float) $l->prix_unitaire,
            ]),
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Support\CurrentGymResolver;
use App\Support\EliteFitGym;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $gymId = $request->user()
            ? CurrentGymResolver::id()
            : EliteFitGym::id();

        $query = Produit::query();

        if (! $request->user()) {
            $query->withoutGlobalScope('current_gym');
        }

        $query->where('gym_id', $gymId)->actif();

        if ($request->filled('categorie')) {
            $query->where('categorie', $request->string('categorie'));
        }

        if ($request->boolean('featured')) {
            $query->where('featured', true);
        }

        $produits = $query->orderBy('nom')->get()->map(fn (Produit $p): array => $this->format($p));

        return response()->json(['data' => $produits]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $gymId = $request->user()
            ? CurrentGymResolver::id()
            : EliteFitGym::id();

        $query = Produit::query();

        if (! $request->user()) {
            $query->withoutGlobalScope('current_gym');
        }

        $produit = $query
            ->where('gym_id', $gymId)
            ->actif()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['data' => $this->format($produit)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateProduct($request);

        $produit = Produit::query()->create([
            ...$data,
            'gym_id' => CurrentGymResolver::id(),
        ]);

        return response()->json(['data' => $this->format($produit)], 201);
    }

    public function update(Request $request, Produit $produit): JsonResponse
    {
        $this->ensureGym($produit);

        $data = $this->validateProduct($request, partial: true);
        $produit->update($data);

        return response()->json(['data' => $this->format($produit->fresh())]);
    }

    public function updateStock(Request $request, Produit $produit): JsonResponse
    {
        $this->ensureGym($produit);

        $data = $request->validate(['stock' => ['required', 'integer', 'min:0']]);
        $produit->update($data);

        return response()->json(['data' => $this->format($produit)]);
    }

    public function destroy(Produit $produit): JsonResponse
    {
        $this->ensureGym($produit);
        $produit->delete();

        return response()->json(['message' => 'Produit supprimé.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function format(Produit $produit): array
    {
        return [
            'id' => $produit->id,
            'nom' => $produit->nom,
            'slug' => $produit->slug,
            'description' => $produit->description,
            'prix' => (float) $produit->prix,
            'stock' => $produit->stock,
            'categorie' => $produit->categorie,
            'image' => $produit->image,
            'featured' => $produit->featured,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProduct(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'nom' => [$required, 'string', 'max:190'],
            'prix' => [$required, 'numeric', 'min:0'],
            'stock' => [$required, 'integer', 'min:0'],
            'categorie' => [$required, 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
            'actif' => ['sometimes', 'boolean'],
            'featured' => ['sometimes', 'boolean'],
        ]);
    }

    private function ensureGym(Produit $produit): void
    {
        if ((int) $produit->gym_id !== CurrentGymResolver::id()) {
            abort(404);
        }
    }
}

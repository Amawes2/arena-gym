<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminStatsService;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function stats(AdminStatsService $stats): JsonResponse
    {
        return response()->json([
            'kpis' => $stats->dashboard(),
            'presences_7j' => $stats->presences7Jours(),
            'derniers_membres' => $stats->derniersMembres(),
            'dernieres_commandes' => $stats->dernieresCommandes(),
        ]);
    }

    public function presences7Jours(AdminStatsService $stats): JsonResponse
    {
        return response()->json(['data' => $stats->presences7Jours()]);
    }
}

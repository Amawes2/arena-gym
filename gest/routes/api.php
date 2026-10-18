<?php

use App\Http\Controllers\Api\AbonnementController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\MembreController;
use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\ProduitController;
use App\Http\Controllers\Api\SeanceController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/register-rapide', [AuthController::class, 'registerRapide'])->middleware('throttle:api-auth');
    Route::post('/abonnement', [AuthController::class, 'abonnement'])->middleware('throttle:api-auth');
});

Route::get('/abonnements/forfaits', [AbonnementController::class, 'forfaits']);
Route::get('/produits', [ProduitController::class, 'index']);
Route::get('/produits/{slug}', [ProduitController::class, 'show']);
Route::post('/seances/reserver', [SeanceController::class, 'reserver'])->middleware('throttle:5,1');
Route::get('/billing/countries', [BillingController::class, 'countries']);

Route::middleware(['auth:web'])->group(function (): void {
    Route::get('/billing/status', [BillingController::class, 'status']);
    Route::post('/billing/renewal-request', [BillingController::class, 'requestRenewal'])->middleware('throttle:5,1');
    Route::post('/onboarding/complete', [BillingController::class, 'completeOnboarding'])->middleware('throttle:10,1');
    Route::get('/support/messages', [SupportController::class, 'index'])->middleware('throttle:30,1');
    Route::post('/support/messages', [SupportController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware(['auth:web', AdminOnly::class])->group(function (): void {
    Route::post('/membres', [MembreController::class, 'store']);
    Route::get('/membres', [MembreController::class, 'index']);
    Route::get('/membres/{membre}', [MembreController::class, 'show']);
    Route::put('/membres/{membre}', [MembreController::class, 'update']);
    Route::delete('/membres/{membre}', [MembreController::class, 'destroy']);

    Route::post('/abonnements', [AbonnementController::class, 'store']);
    Route::put('/abonnements/{subscription}/renouveler', [AbonnementController::class, 'renouveler']);

    Route::get('/seances', [SeanceController::class, 'index']);
    Route::post('/presences', [PresenceController::class, 'store']);

    Route::post('/produits', [ProduitController::class, 'store']);
    Route::put('/produits/{produit}/stock', [ProduitController::class, 'updateStock']);

    Route::get('/commandes', [CommandeController::class, 'index']);
    Route::post('/commandes', [CommandeController::class, 'store']);
    Route::put('/commandes/{commande}/statut', [CommandeController::class, 'updateStatut']);

    Route::get('/admin/stats', [AdminController::class, 'stats']);
    Route::get('/admin/presences/7jours', [AdminController::class, 'presences7Jours']);
});

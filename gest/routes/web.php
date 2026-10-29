<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\GymRegistrationController;
use App\Http\Controllers\MagicLinkController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'elitefit');
Route::prefix('api/auth')->group(function (): void {
    Route::post('/magic-link', [MagicLinkController::class, 'requestLink'])->middleware('throttle:magic-link');
    Route::post('/register-gym', [AuthController::class, 'registerGym'])->middleware('throttle:gym-registration');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-auth');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:api-auth');
    Route::post('/email-verification/verify', [EmailVerificationController::class, 'verify'])->middleware('throttle:email-verification');
    Route::post('/email-verification/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:email-verification-resend');

    Route::middleware('auth')->group(function (): void {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::get('/connexion/magic/{token}', [MagicLinkController::class, 'confirm'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('signed')
    ->name('auth.magic.confirm');
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');
Route::post('/connexion/magic/{token}', [MagicLinkController::class, 'consume'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware(['signed', 'throttle:magic-link-consume'])
    ->name('auth.magic.consume');
Route::post('/inscription-salle', [GymRegistrationController::class, 'store'])
    ->middleware('throttle:gym-registration')
    ->name('gyms.register.store');

Route::view('/{any}', 'elitefit')
    ->where('any', '^(?!api|backoffice|super-admin|up|storage).+');

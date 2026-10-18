<?php

use App\Enums\UserRole;
use App\Models\PendingRegistration;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;

Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::call(fn (): int => PendingRegistration::query()
    ->where('registration_expires_at', '<=', now())
    ->delete())->hourly();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('elitefit:make-admin {email} {password} {--name=Admin EliteFit}', function (): void {
    $email = strtolower(trim((string) $this->argument('email')));
    $password = (string) $this->argument('password');
    $name = trim((string) $this->option('name'));

    if (strlen($password) < 4) {
        $this->error('Le mot de passe doit contenir au moins 4 caractères.');

        return;
    }

    $user = User::query()->updateOrCreate(
        ['email' => $email],
        [
            'name' => $name !== '' ? $name : 'Admin EliteFit',
            'password' => Hash::make($password),
            'role' => UserRole::Admin->value,
            'email_verified_at' => now(),
        ]
    );

    $this->info("Administrateur prêt : {$user->email}");
    $this->line('Connexion React admin : /admin/login');
})->purpose('Créer ou réinitialiser un compte administrateur EliteFit');

<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Gym;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class MakeFilamentUserCommand extends Command
{
    protected $signature = 'make:filament-user
        {--role= : Role à attribuer : admin ou super_admin}
        {--panel= : Alias compatible : admin ou platform}
        {--name= : Nom du compte}
        {--email= : Adresse e-mail unique}
        {--password= : Mot de passe du compte}
        {--gym= : Identifiant de la salle pour un admin}';

    protected $description = 'Créer un utilisateur Filament avec le rôle admin ou super admin';

    public function handle(): int
    {
        $role = $this->resolveRole();

        if ($role === null) {
            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Nom complet')));

        if ($name === '') {
            $this->error('Le nom est obligatoire.');

            return self::FAILURE;
        }

        $email = $this->resolveEmail();

        if ($email === null) {
            return self::FAILURE;
        }

        if ($role === UserRole::SuperAdmin && ! $this->isConfiguredSuperAdminEmail($email)) {
            $this->error('Le Super Admin doit utiliser l’adresse définie dans ARENA_SUPER_ADMIN_EMAIL.');

            return self::FAILURE;
        }

        if ($role === UserRole::SuperAdmin && User::query()->where('role', UserRole::SuperAdmin->value)->exists()) {
            $this->error('Un compte Super Admin existe déjà.');

            return self::FAILURE;
        }

        $gym = $role === UserRole::Admin ? $this->resolveGym() : null;

        if ($role === UserRole::Admin && $gym === null) {
            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: $this->secret('Mot de passe'));
        $passwordError = Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', Password::defaults()]],
        )->errors()->first('password');

        if ($passwordError !== '') {
            $this->error($passwordError);

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
        ]);

        if ($gym !== null) {
            $user->managedGyms()->syncWithoutDetaching([$gym->id]);
        }

        $panelId = $role === UserRole::SuperAdmin ? 'platform' : 'admin';
        $loginUrl = Filament::getPanel($panelId)->getLoginUrl();

        $this->info("Compte {$role->value} créé : {$user->email}");
        $this->line("Connexion : {$loginUrl}");

        return self::SUCCESS;
    }

    private function resolveRole(): ?UserRole
    {
        $role = $this->option('role');
        $panel = $this->option('panel');

        if ($role === null && $panel !== null) {
            $role = match ($panel) {
                'admin' => UserRole::Admin->value,
                'platform' => UserRole::SuperAdmin->value,
                default => null,
            };

            if ($role === null) {
                $this->error('Le panel doit être admin ou platform.');

                return null;
            }
        } elseif ($role === null) {
            $role = match ($this->choice('Quel rôle souhaitez-vous attribuer ?', ['Admin salle', 'Super Admin'])) {
                'Admin salle' => UserRole::Admin->value,
                'Super Admin' => UserRole::SuperAdmin->value,
                default => null,
            };
        }

        $resolvedRole = UserRole::tryFrom((string) $role);

        if (! in_array($resolvedRole, [UserRole::Admin, UserRole::SuperAdmin], true)) {
            $this->error('Le rôle doit être admin ou super_admin.');

            return null;
        }

        return $resolvedRole;
    }

    private function resolveEmail(): ?string
    {
        $providedEmail = $this->option('email');

        while (true) {
            $email = strtolower(trim((string) ($providedEmail ?? $this->ask('Adresse e-mail'))));

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error('Veuillez fournir une adresse e-mail valide.');
            } elseif (User::query()->where('email', $email)->exists()) {
                $this->error('Un utilisateur avec cette adresse e-mail existe déjà.');
            } else {
                return $email;
            }

            if ($providedEmail !== null) {
                return null;
            }
        }
    }

    private function resolveGym(): ?Gym
    {
        $gymId = $this->option('gym');

        if ($gymId !== null) {
            $gym = Gym::query()->find($gymId);

            if ($gym === null) {
                $this->error('La salle indiquée est introuvable.');
            }

            return $gym;
        }

        $gyms = Gym::query()->orderBy('name')->get();

        if ($gyms->isEmpty()) {
            $this->error('Aucune salle n’existe. Créez une salle avant son compte admin.');

            return null;
        }

        $choices = $gyms->map(
            fn (Gym $gym): string => "{$gym->id}: {$gym->name}",
        )->all();
        $selectedGym = $this->choice('À quelle salle rattacher cet admin ?', $choices);
        $selectedGymId = (int) strtok($selectedGym, ':');

        return $gyms->firstWhere('id', (int) $selectedGymId);
    }

    private function isConfiguredSuperAdminEmail(string $email): bool
    {
        $configuredEmail = strtolower(trim((string) config('elitefit.super_admin_email')));

        return $configuredEmail !== '' && $configuredEmail === $email;
    }
}

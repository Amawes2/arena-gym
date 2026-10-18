<?php

namespace App\Support;

use App\Models\Gym;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class CurrentGymResolver
{
    public static function id(): int
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Gym) {
            return (int) $tenant->getKey();
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            throw new AuthorizationException('Aucune salle active n’est sélectionnée.');
        }

        $requestedGymId = request()->integer('gym_id');

        if ($requestedGymId > 0) {
            abort_unless($user->canAccessGym($requestedGymId), 404);

            return $requestedGymId;
        }

        if ($user->isSuperAdmin()) {
            throw new AuthorizationException('Le Super Admin doit sélectionner une salle.');
        }

        $gymIds = $user->gyms()->pluck('id')->merge($user->managedGyms()->pluck('gyms.id'))->unique();

        if ($gymIds->count() !== 1) {
            throw new AuthorizationException('Sélectionnez une salle autorisée.');
        }

        return (int) $gymIds->first();
    }

    public static function resolve(): ?Gym
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof Gym) {
            return $tenant;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            return null;
        }

        $gymIds = $user->gyms()->pluck('id')->merge($user->managedGyms()->pluck('gyms.id'))->unique();

        if ($gymIds->count() !== 1) {
            return null;
        }

        return Gym::query()->find((int) $gymIds->first());
    }
}

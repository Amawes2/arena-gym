<?php

namespace App\Models\Concerns;

use App\Models\Gym;
use App\Models\Subscription;
use App\Models\SupportMessage;
use App\Models\User;
use App\Services\GymAccessService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait BelongsToCurrentGym
{
    protected static function bootBelongsToCurrentGym(): void
    {
        static::creating(function ($model): void {
            $user = Auth::user();

            if (! empty($model->gym_id)) {
                if ($user instanceof User && ! $user->canAccessGym((int) $model->gym_id)) {
                    throw new AuthorizationException('Accès refusé à cette salle.');
                }

                static::assertCurrentGymCanWrite($model, (int) $model->gym_id, $user);

                return;
            }

            $gymId = static::resolveCurrentGymId();

            if ($gymId === -1) {
                throw new \InvalidArgumentException('L’utilisateur n’a pas de salle de sport assignée.');
            }

            if ($gymId === null && $user instanceof User && ! app()->runningInConsole()) {
                throw new \InvalidArgumentException('Sélectionnez une salle avant de créer cette ressource.');
            }

            if ($gymId !== null) {
                $model->gym_id = $gymId;
                static::assertCurrentGymCanWrite($model, $gymId, $user);
            }
        });

        static::updating(function ($model): void {
            $user = Auth::user();
            $gymId = (int) $model->getOriginal('gym_id');

            if ($gymId < 1 || (int) $model->gym_id !== $gymId) {
                throw new AuthorizationException('Cette ressource ne peut pas être déplacée vers une autre salle.');
            }

            if ($user instanceof User && ! $user->canAccessGym($gymId)) {
                throw new AuthorizationException('Accès refusé à cette salle.');
            }

            static::assertCurrentGymCanWrite($model, $gymId, $user);
        });

        static::deleting(function ($model): void {
            $user = Auth::user();
            $gymId = (int) $model->gym_id;

            if ($gymId < 1 || ($user instanceof User && ! $user->canAccessGym($gymId))) {
                throw new AuthorizationException('Accès refusé à cette salle.');
            }

            static::assertCurrentGymCanDelete($model, $gymId, $user);
        });

        static::addGlobalScope('current_gym', function (Builder $query): void {
            $gymId = static::resolveCurrentGymId();

            if ($gymId === null) {
                return;
            }

            $query->where($query->getModel()->getTable().'.gym_id', $gymId);
        });
    }

    protected static function resolveCurrentGymId(): ?int
    {
        $tenantId = Filament::getTenant()?->getKey();

        if ($tenantId) {
            return (int) $tenantId;
        }

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return app()->runningInConsole() ? null : -1;
        }

        $gymIds = $user->gyms()->pluck('id')->merge($user->managedGyms()->pluck('gyms.id'))->unique();

        if ($user->isSuperAdmin()) {
            $requestedGymId = request()->integer('gym_id');

            if ($requestedGymId > 0) {
                return $user->canAccessGym($requestedGymId) ? $requestedGymId : -1;
            }

            return null;
        }

        $requestedGymId = request()->integer('gym_id');

        if ($requestedGymId > 0) {
            return $user->canAccessGym($requestedGymId) ? $requestedGymId : -1;
        }

        if ($gymIds->count() !== 1) {
            return -1;
        }

        return (int) $gymIds->first();
    }

    private static function assertCurrentGymCanWrite(object $model, int $gymId, ?User $user): void
    {
        if (! $user instanceof User || $user->isSuperAdmin() || $model instanceof SupportMessage) {
            return;
        }

        $gymQuery = Gym::query()->whereKey($gymId);

        if ($model instanceof Subscription && DB::transactionLevel() > 0) {
            $gymQuery->lockForUpdate();
        }

        app(GymAccessService::class)->assertCanWrite($gymQuery->firstOrFail(), $model);
    }

    private static function assertCurrentGymCanDelete(object $model, int $gymId, ?User $user): void
    {
        if (! $user instanceof User || $user->isSuperAdmin() || $model instanceof SupportMessage) {
            return;
        }

        $gym = Gym::query()->findOrFail($gymId);
        app(GymAccessService::class)->assertCanDelete($gym, $model);
    }
}

<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'telephone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $role = $user->attributes['role'] ?? null;
            $originalRole = $user->original['role'] ?? null;

            if ($role !== UserRole::SuperAdmin->value) {
                return;
            }

            if (! $user->isSuperAdmin()) {
                throw ValidationException::withMessages([
                    'email' => 'Seule l’adresse Super Admin configurée peut recevoir ce rôle.',
                ]);
            }

            if ($originalRole === $role) {
                return;
            }

            $anotherSuperAdminExists = static::query()
                ->where('role', UserRole::SuperAdmin->value)
                ->when($user->exists, fn ($query) => $query->whereKeyNot($user->getKey()))
                ->exists();

            if ($anotherSuperAdminExists) {
                throw ValidationException::withMessages([
                    'role' => 'Un seul compte Super Admin est autorise.',
                ]);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarding_completed_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function gyms(): HasMany
    {
        return $this->hasMany(Gym::class, 'owner_id');
    }

    public function managedGyms(): BelongsToMany
    {
        return $this->belongsToMany(Gym::class, 'gym_user');
    }

    public function getTenants(Panel $panel): array|Collection
    {
        if ($this->isSuperAdmin()) {
            return Gym::query()->orderBy('name')->get();
        }

        // Return both owned gyms and gyms the user has been assigned to manage
        return $this->gyms()->get()->merge($this->managedGyms()->get())->unique('id')->values();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->isSuperAdmin()) {
            return $tenant instanceof Gym;
        }

        return $this->gyms()->whereKey($tenant)->exists()
            || $this->managedGyms()->whereKey($tenant)->exists();
    }

    public function isSuperAdmin(): bool
    {
        $configuredEmail = strtolower(trim((string) config('elitefit.super_admin_email')));

        return ($this->attributes['role'] ?? null) === UserRole::SuperAdmin->value
            && $configuredEmail !== ''
            && strtolower(trim((string) $this->email)) === $configuredEmail;
    }

    public function roleEnum(): ?UserRole
    {
        $role = UserRole::tryFrom((string) ($this->attributes['role'] ?? ''));

        if ($role === UserRole::SuperAdmin && ! $this->isSuperAdmin()) {
            return null;
        }

        return $role;
    }

    public function canAccessGym(int $gymId): bool
    {
        return $this->isSuperAdmin()
            || $this->gyms()->whereKey($gymId)->exists()
            || $this->managedGyms()->whereKey($gymId)->exists();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->getTenants($panel)->first() ?: null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'platform') {
            return $this->isSuperAdmin();
        }

        if ($panel->getId() !== 'admin') {
            return false;
        }

        $role = $this->roleEnum();

        if (! in_array($role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Receptionist], true)) {
            return false;
        }

        return $role === UserRole::SuperAdmin
            || $this->gyms()->exists()
            || $this->managedGyms()->exists();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'owner_id', 'plan_saas', 'is_active', 'is_approved', 'country_code', 'currency_code', 'expires_at', 'walk_in_price'])]
class Gym extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_approved' => 'boolean',
            'provisional_subscriptions_created' => 'integer',
            'expires_at' => 'datetime',
            'walk_in_price' => 'float',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getSaasStatus(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        if (! $this->is_approved) {
            return 'pending_approval';
        }

        if (! $this->expires_at) {
            return 'unknown';
        }

        if ($this->expires_at->lt(today())) {
            return 'expired';
        }

        if ($this->expires_at->diffInDays(now()) <= 7) {
            return 'expiring';
        }

        return 'active';
    }

    public function isSaasReadOnly(): bool
    {
        if (! $this->is_active) {
            return true;
        }

        if (! $this->is_approved) {
            return false;
        }

        return ! $this->expires_at || $this->expires_at->lt(today());
    }

    public function memberSubscriptionCreationLimit(): ?int
    {
        return $this->is_approved ? null : 3;
    }

    public function provisionalSubscriptionCount(): int
    {
        return (int) $this->provisional_subscriptions_created;
    }

    public function getSaasStatusLabel(): string
    {
        return match ($this->getSaasStatus()) {
            'expired' => 'Expiré',
            'expiring' => 'À relancer',
            'pending_approval' => 'A valider',
            'active' => 'Actif',
            'inactive' => 'Désactivé',
            default => 'À vérifier',
        };
    }

    public function getSaasStatusColor(): string
    {
        return match ($this->getSaasStatus()) {
            'expired' => 'danger',
            'expiring' => 'warning',
            'pending_approval' => 'warning',
            'active' => 'success',
            'inactive' => 'gray',
            default => 'gray',
        };
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->expires_at->copy()->startOfDay(), false);
    }

    public function needsSaasReminder(): bool
    {
        return in_array($this->getSaasStatus(), ['expired', 'expiring', 'pending_approval', 'unknown'], true);
    }

    public function getExpirySummary(): string
    {
        if (! $this->expires_at) {
            return 'Aucune échéance';
        }

        $days = $this->getDaysUntilExpiry();

        if ($days === null) {
            return 'Aucune échéance';
        }

        if ($days < 0) {
            $delay = abs($days);

            return 'Expiré depuis '.$delay.' jour'.($delay > 1 ? 's' : '');
        }

        if ($days === 0) {
            return 'Expire aujourd’hui';
        }

        return 'Expire dans '.$days.' jour'.($days > 1 ? 's' : '');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }
}

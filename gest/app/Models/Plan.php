<?php

namespace App\Models;

use App\Enums\PlanBillingPeriod;
use App\Models\Concerns\BelongsToCurrentGym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use BelongsToCurrentGym;
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'gym_id',
        'name',
        'description',
        'price',
        'duration_days',
        'billing_period',
        'sessions_limit',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sessions_limit' => 'integer',
            'duration_days' => 'integer',
            'billing_period' => PlanBillingPeriod::class,
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @param  Builder<Plan>  $query
     * @return Builder<Plan>
     */
    public function scopeActive($query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isUnlimitedSessions(): bool
    {
        return $this->sessions_limit === null;
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }
}

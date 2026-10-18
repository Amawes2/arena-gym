<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Models\Concerns\BelongsToCurrentGym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Subscription extends Model
{
    use BelongsToCurrentGym;
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (Subscription $subscription) => self::validateRelatedRecords($subscription));
        static::updating(fn (Subscription $subscription) => self::validateRelatedRecords($subscription));
    }

    /** @var list<string> */
    protected $fillable = [
        'gym_id',
        'member_id',
        'plan_id',
        'start_date',
        'end_date',
        'sessions_remaining',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'sessions_remaining' => 'integer',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isExpired(): bool
    {
        return $this->end_date->lt(today()) || $this->status === SubscriptionStatus::Expired;
    }

    public function hasSessionsLeft(): bool
    {
        if ($this->sessions_remaining === null) {
            return true;
        }

        return $this->sessions_remaining > 0;
    }

    public function isAccessible(): bool
    {
        return $this->status === SubscriptionStatus::Active
            && $this->end_date->gte(today())
            && $this->hasSessionsLeft();
    }

    /**
     * @param  Builder<Subscription>  $query
     * @return Builder<Subscription>
     */
    public function scopeActive($query): Builder
    {
        return $query
            ->where('status', SubscriptionStatus::Active->value)
            ->where('end_date', '>=', now()->toDateString());
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    private static function validateRelatedRecords(Subscription $subscription): void
    {
        $member = Member::withoutGlobalScopes()->find($subscription->member_id);
        $plan = Plan::withoutGlobalScopes()->find($subscription->plan_id);

        if (! $member || (int) $member->gym_id !== (int) $subscription->gym_id) {
            throw ValidationException::withMessages([
                'member_id' => 'Le membre doit appartenir a la meme salle que l abonnement.',
            ]);
        }

        if (! $plan || (int) $plan->gym_id !== (int) $subscription->gym_id) {
            throw ValidationException::withMessages([
                'plan_id' => 'Le forfait doit appartenir a la meme salle que l abonnement.',
            ]);
        }
    }
}

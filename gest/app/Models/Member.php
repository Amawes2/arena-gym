<?php

namespace App\Models;

use App\Enums\ClientType;
use App\Enums\SubscriptionStatus;
use App\Models\Concerns\BelongsToCurrentGym;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use BelongsToCurrentGym;
    use HasFactory;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (Member $member): void {
            $member->name = trim($member->first_name.' '.$member->last_name) ?: '-';
        });

    }

    /** @var list<string> */
    protected $fillable = [
        'gym_id',
        'name',
        'first_name',
        'last_name',
        'phone',
        'email',
        'id_document_number',
        'photo_path',
        'bio',
        'client_type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'client_type' => ClientType::class,
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relation HasOne vers l'abonnement actif le plus récent.
     * Utilise latestOfMany() pour être eager-loadable (notation pointée Filament).
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', SubscriptionStatus::Active->value)
            ->where('end_date', '>=', now()->toDateString())
            ->latestOfMany('start_date');
    }

    public function isSubscriber(): bool
    {
        return $this->client_type === ClientType::Subscriber;
    }

    public function isWalkIn(): bool
    {
        return $this->client_type === ClientType::WalkIn;
    }

    /**
     * @param  Builder<Member>  $query
     * @return Builder<Member>
     */
    public function scopeSubscribers($query): Builder
    {
        return $query->where('client_type', ClientType::Subscriber->value);
    }

    /**
     * @param  Builder<Member>  $query
     * @return Builder<Member>
     */
    public function scopeWalkIns($query): Builder
    {
        return $query->where('client_type', ClientType::WalkIn->value);
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }
}

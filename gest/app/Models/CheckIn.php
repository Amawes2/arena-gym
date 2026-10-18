<?php

namespace App\Models;

use App\Enums\CheckInType;
use App\Models\Concerns\BelongsToCurrentGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckIn extends Model
{
    use BelongsToCurrentGym;
    use HasFactory;

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'gym_id',
        'member_id',
        'subscription_id',
        'type',
        'checked_in_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CheckInType::class,
            'checked_in_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isWalkIn(): bool
    {
        return $this->type === CheckInType::WalkIn;
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }
}

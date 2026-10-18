<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Concerns\BelongsToCurrentGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Payment extends Model
{
    use BelongsToCurrentGym;
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Payment $payment): void {
            $payment->status ??= PaymentStatus::Completed;
            $payment->paid_at = $payment->status === PaymentStatus::Completed
                ? ($payment->paid_at ?? now())
                : null;
        });

        static::creating(fn (Payment $payment) => self::validateRelatedRecords($payment));
        static::updating(fn (Payment $payment) => self::validateRelatedRecords($payment));

        static::updated(function (Payment $payment): void {
            if (! $payment->wasChanged('status') || $payment->status !== PaymentStatus::Completed) {
                return;
            }

            $subscription = $payment->subscription()
                ->where('gym_id', $payment->gym_id)
                ->first();

            if ($subscription?->status === SubscriptionStatus::Pending) {
                $subscription->update(['status' => SubscriptionStatus::Active]);
            }
        });
    }

    /** @var list<string> */
    protected $fillable = [
        'gym_id',
        'member_id',
        'subscription_id',
        'amount',
        'payment_method',
        'reference',
        'status',
        'paid_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
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

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    private static function validateRelatedRecords(Payment $payment): void
    {
        $member = Member::withoutGlobalScopes()->find($payment->member_id);

        if (! $member || (int) $member->gym_id !== (int) $payment->gym_id) {
            throw ValidationException::withMessages([
                'member_id' => 'Le membre doit appartenir a la meme salle que le paiement.',
            ]);
        }

        if (! $payment->subscription_id) {
            return;
        }

        $subscription = Subscription::withoutGlobalScopes()->find($payment->subscription_id);

        if (! $subscription
            || (int) $subscription->gym_id !== (int) $payment->gym_id
            || (int) $subscription->member_id !== (int) $payment->member_id) {
            throw ValidationException::withMessages([
                'subscription_id' => 'L abonnement doit appartenir au membre et a la meme salle que le paiement.',
            ]);
        }
    }
}

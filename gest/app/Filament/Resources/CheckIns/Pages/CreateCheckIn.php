<?php

namespace App\Filament\Resources\CheckIns\Pages;

use App\Enums\CheckInType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\CheckIns\CheckInResource;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCheckIn extends CreateRecord
{
    protected static string $resource = CheckInResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $member = Member::query()->findOrFail($data['member_id']);
            $checkInType = CheckInType::from((string) $data['type']);
            $paymentAmount = $data['payment_amount'] ?? null;
            $paymentMethod = $data['payment_method'] ?? null;

            unset($data['payment_amount'], $data['payment_method']);

            if ($checkInType === CheckInType::Subscription) {
                $subscription = $this->resolveSubscription($member, $data['subscription_id'] ?? null);

                $data['subscription_id'] = $subscription->id;

                if ($subscription->sessions_remaining !== null) {
                    $subscription->sessions_remaining--;

                    if ($subscription->sessions_remaining <= 0) {
                        $subscription->sessions_remaining = 0;
                        $subscription->status = SubscriptionStatus::Expired;
                    }

                    $subscription->save();
                }
            } else {
                $data['subscription_id'] = null;
                $this->createWalkInPayment($member, $paymentAmount, $paymentMethod);
            }

            $data['checked_in_at'] = now();

            return static::getModel()::query()->create($data);
        });
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Check-in validé')
            ->success()
            ->body('Entrée enregistrée avec succès.')
            ->send();
    }

    private function resolveSubscription(Member $member, mixed $subscriptionId): Subscription
    {
        if (! $subscriptionId) {
            throw ValidationException::withMessages([
                'data.subscription_id' => 'Un abonnement actif est requis pour ce check-in.',
            ]);
        }

        $subscription = Subscription::query()
            ->whereKey($subscriptionId)
            ->whereBelongsTo($member)
            ->lockForUpdate()
            ->first();

        if (! $subscription || ! $subscription->isAccessible()) {
            throw ValidationException::withMessages([
                'data.subscription_id' => 'Cet abonnement est expiré ou ne peut plus être utilisé.',
            ]);
        }

        return $subscription;
    }

    private function createWalkInPayment(Member $member, mixed $paymentAmount, mixed $paymentMethod): void
    {
        if (! is_numeric($paymentAmount) || (float) $paymentAmount <= 0) {
            throw ValidationException::withMessages([
                'data.payment_amount' => 'Le montant du ticket doit être supérieur à 0.',
            ]);
        }

        if (! is_string($paymentMethod) || ! PaymentMethod::tryFrom($paymentMethod)) {
            throw ValidationException::withMessages([
                'data.payment_method' => 'Le mode de paiement est obligatoire pour un ticket séance unique.',
            ]);
        }

        Payment::query()->create([
            'member_id' => $member->id,
            'subscription_id' => null,
            'amount' => $paymentAmount,
            'payment_method' => $paymentMethod,
            'status' => PaymentStatus::Completed->value,
            'paid_at' => now(),
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\Subscription;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GymAccessService
{
    public const PROVISIONAL_SUBSCRIPTION_LIMIT = 3;

    public function isReadOnly(Gym $gym): bool
    {
        return $gym->isSaasReadOnly();
    }

    public function assertCanWrite(Gym $gym, ?object $record = null): void
    {
        if ($record instanceof SupportMessage) {
            return;
        }

        if ($this->isReadOnly($gym)) {
            throw ValidationException::withMessages([
                'subscription' => 'Votre espace est en lecture seule. Renouvelez l’abonnement ou contactez le Super Admin pour réactiver les modifications.',
            ]);
        }

        if ($record instanceof Subscription && ! $record->exists && ! $gym->is_approved) {
            if (DB::transactionLevel() < 1) {
                throw new \LogicException('La création d’un abonnement provisoire doit être exécutée dans une transaction.');
            }

            $lockedGym = Gym::query()->whereKey($gym->id)->lockForUpdate()->firstOrFail();

            if ($lockedGym->provisionalSubscriptionCount() >= self::PROVISIONAL_SUBSCRIPTION_LIMIT) {
                throw ValidationException::withMessages([
                    'subscription' => 'La salle peut créer au maximum trois abonnements tant que le Super Admin ne l’a pas validée.',
                ]);
            }

            $lockedGym->increment('provisional_subscriptions_created');
        }
    }

    public function assertCanDelete(Gym $gym, ?object $record = null): void
    {
        $this->assertCanWrite($gym, $record);

        if ($record instanceof Subscription && ! $gym->is_approved) {
            throw ValidationException::withMessages([
                'subscription' => 'Les abonnements provisoires ne peuvent pas être supprimés avant la validation de la salle.',
            ]);
        }
    }
}

<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            SubscriptionStatus::Pending => 'En attente de paiement',
            SubscriptionStatus::Active => 'Actif',
            SubscriptionStatus::Expired => 'Expiré',
            SubscriptionStatus::Cancelled => 'Annulé',
        };
    }
}

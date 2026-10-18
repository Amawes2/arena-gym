<?php

namespace App\Enums;

enum CheckInType: string
{
    case Subscription = 'subscription';
    case WalkIn = 'walk_in';

    public function label(): string
    {
        return match ($this) {
            CheckInType::Subscription => 'Abonnement',
            CheckInType::WalkIn => 'Séance unique',
        };
    }
}

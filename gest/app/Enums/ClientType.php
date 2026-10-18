<?php

namespace App\Enums;

enum ClientType: string
{
    case Subscriber = 'subscriber';
    case WalkIn = 'walk_in';

    public function label(): string
    {
        return match ($this) {
            ClientType::Subscriber => 'Abonné',
            ClientType::WalkIn => 'Séance unique',
        };
    }
}

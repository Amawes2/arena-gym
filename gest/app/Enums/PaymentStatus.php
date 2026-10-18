<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            PaymentStatus::Pending => 'En attente',
            PaymentStatus::Completed => 'Complété',
            PaymentStatus::Failed => 'Échoué',
        };
    }
}

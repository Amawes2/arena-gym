<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case MobileMoney = 'mobile_money';
    case Wave = 'wave';
    case OrangeMoney = 'orange_money';
    case MtnMoney = 'mtn_money';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            PaymentMethod::Cash => 'Espèces',
            PaymentMethod::MobileMoney => 'Mobile Money',
            PaymentMethod::Wave => 'Wave',
            PaymentMethod::OrangeMoney => 'Orange Money',
            PaymentMethod::MtnMoney => 'MTN Money',
            PaymentMethod::Card => 'Carte bancaire',
            PaymentMethod::BankTransfer => 'Virement',
        };
    }
}

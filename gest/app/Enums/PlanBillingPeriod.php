<?php

namespace App\Enums;

enum PlanBillingPeriod: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Annual = 'annual';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            PlanBillingPeriod::Monthly => 'Mensuel (+1 mois)',
            PlanBillingPeriod::Quarterly => 'Trimestriel (+3 mois)',
            PlanBillingPeriod::Annual => 'Annuel (+1 an)',
            PlanBillingPeriod::Custom => 'Personnalisé',
        };
    }
}

<?php

namespace App\Support;

use App\Enums\PlanBillingPeriod;
use App\Models\Plan;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class PlanEndDateCalculator
{
    /**
     * Calcule la date de fin d'abonnement (jour inclus côté début : le lendemain de la veille de fin).
     * Logique « calendrier » pour mensuel / trimestriel / annuel.
     */
    public static function endDateFor(Plan $plan, CarbonInterface|string $startDate): Carbon
    {
        $start = Carbon::parse($startDate)->startOfDay();

        $period = $plan->billing_period;

        if ($period instanceof PlanBillingPeriod) {
            return match ($period) {
                PlanBillingPeriod::Monthly => $start->copy()->addMonth(),
                PlanBillingPeriod::Quarterly => $start->copy()->addMonths(3),
                PlanBillingPeriod::Annual => $start->copy()->addYear(),
                PlanBillingPeriod::Custom => $start->copy()->addDays(max(1, (int) $plan->duration_days)),
            };
        }

        return $start->copy()->addDays(max(1, (int) $plan->duration_days));
    }
}

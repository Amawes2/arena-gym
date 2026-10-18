<?php

namespace Tests\Unit;

use App\Enums\PlanBillingPeriod;
use App\Models\Plan;
use App\Support\PlanEndDateCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PlanEndDateCalculatorTest extends TestCase
{
    #[Test]
    public function it_adds_one_calendar_month_for_monthly_plans(): void
    {
        $plan = new Plan([
            'billing_period' => PlanBillingPeriod::Monthly,
            'duration_days' => 30,
        ]);

        $end = PlanEndDateCalculator::endDateFor($plan, '2026-01-15');

        $this->assertSame('2026-02-15', $end->toDateString());
    }

    #[Test]
    public function it_adds_three_calendar_months_for_quarterly_plans(): void
    {
        $plan = new Plan([
            'billing_period' => PlanBillingPeriod::Quarterly,
            'duration_days' => 90,
        ]);

        $end = PlanEndDateCalculator::endDateFor($plan, '2026-01-15');

        $this->assertSame('2026-04-15', $end->toDateString());
    }

    #[Test]
    public function it_adds_one_calendar_year_for_annual_plans(): void
    {
        $plan = new Plan([
            'billing_period' => PlanBillingPeriod::Annual,
            'duration_days' => 365,
        ]);

        $end = PlanEndDateCalculator::endDateFor($plan, '2026-01-15');

        $this->assertSame('2027-01-15', $end->toDateString());
    }

    #[Test]
    public function it_falls_back_to_duration_days_when_billing_period_is_null(): void
    {
        $plan = new Plan([
            'billing_period' => null,
            'duration_days' => 7,
        ]);

        $end = PlanEndDateCalculator::endDateFor($plan, '2026-05-01');

        $this->assertSame('2026-05-08', $end->toDateString());
    }
}

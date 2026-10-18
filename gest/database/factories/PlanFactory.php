<?php

namespace Database\Factories;

use App\Enums\PlanBillingPeriod;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $period = $this->faker->randomElement([
            PlanBillingPeriod::Monthly,
            PlanBillingPeriod::Quarterly,
            PlanBillingPeriod::Annual,
        ]);
        $days = match ($period) {
            PlanBillingPeriod::Monthly => 30,
            PlanBillingPeriod::Quarterly => 90,
            PlanBillingPeriod::Annual => 365,
        };

        return [
            'name' => 'Forfait '.$period->label(),
            'price' => $this->faker->randomElement([5000, 10000, 15000, 25000, 50000]),
            'billing_period' => $period,
            'duration_days' => $days,
            'sessions_limit' => $this->faker->optional(0.4)->numberBetween(8, 30),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function monthly(): static
    {
        return $this->state([
            'name' => 'Mensuel',
            'billing_period' => PlanBillingPeriod::Monthly,
            'duration_days' => 30,
            'price' => 15000,
        ]);
    }

    public function annual(): static
    {
        return $this->state([
            'name' => 'Annuel',
            'billing_period' => PlanBillingPeriod::Annual,
            'duration_days' => 365,
            'price' => 120000,
        ]);
    }
}

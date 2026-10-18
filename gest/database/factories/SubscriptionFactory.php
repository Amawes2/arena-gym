<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $plan = Plan::inRandomOrder()->first() ?? Plan::factory()->monthly()->create();
        $startDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $endDate = (clone $startDate)->modify("+{$plan->duration_days} days");

        $status = $endDate < now()
            ? SubscriptionStatus::Expired
            : SubscriptionStatus::Active;

        return [
            'member_id' => Member::factory()->subscriber(),
            'plan_id' => $plan->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'sessions_remaining' => $plan->sessions_limit,
            'status' => $status,
        ];
    }

    public function active(): static
    {
        return $this->state(function () {
            $plan = Plan::inRandomOrder()->first() ?? Plan::factory()->monthly()->create();

            return [
                'plan_id' => $plan->id,
                'start_date' => now()->subDays(5)->toDateString(),
                'end_date' => now()->addDays($plan->duration_days - 5)->toDateString(),
                'sessions_remaining' => $plan->sessions_limit,
                'status' => SubscriptionStatus::Active,
            ];
        });
    }

    public function expiringSoon(): static
    {
        return $this->state([
            'start_date' => now()->subDays(27)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'status' => SubscriptionStatus::Active,
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'start_date' => now()->subDays(40)->toDateString(),
            'end_date' => now()->subDays(10)->toDateString(),
            'status' => SubscriptionStatus::Expired,
        ]);
    }
}

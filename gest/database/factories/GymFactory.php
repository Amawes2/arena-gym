<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gym>
 */
class GymFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Gym',
            'slug' => fake()->unique()->slug(),
            'owner_id' => User::factory(),
            'plan_saas' => 'basic',
            'is_active' => true,
            'is_approved' => true,
            'country_code' => 'CI',
            'currency_code' => 'XOF',
            'provisional_subscriptions_created' => 0,
            'expires_at' => now()->addMonth(),
            'walk_in_price' => 3500,
        ];
    }
}

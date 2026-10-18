<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportMessage>
 */
class SupportMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'user_id' => User::factory(),
            'sender_role' => 'admin',
            'category' => 'message',
            'body' => fake()->sentence(),
            'read_at' => null,
        ];
    }
}

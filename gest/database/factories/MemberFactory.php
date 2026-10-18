<?php

namespace Database\Factories;

use App\Enums\ClientType;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $clientType = $this->faker->randomElement(ClientType::cases());

        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'phone' => '0'.fake()->numerify('########'),
            'email' => $this->faker->optional(0.6)->safeEmail(),
            'bio' => $this->faker->optional(0.3)->sentence(),
            'client_type' => $clientType,
        ];
    }

    public function subscriber(): static
    {
        return $this->state(['client_type' => ClientType::Subscriber]);
    }

    public function walkIn(): static
    {
        return $this->state(['client_type' => ClientType::WalkIn]);
    }
}

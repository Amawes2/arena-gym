<?php

namespace Database\Factories;

use App\Models\CheckIn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckIn>
 */
class CheckInFactory extends Factory
{
    protected $model = CheckIn::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            // Géré par le seeder
        ];
    }
}

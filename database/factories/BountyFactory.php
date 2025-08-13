<?php

namespace Database\Factories;

use App\Models\Bounty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bounty>
 */
class BountyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => $this->faker->randomElement(['open', 'closed']),
            'description' => $this->faker->sentence(),
            'reward_xp' => $this->faker->numberBetween(5, 100),

        ];
    }
}

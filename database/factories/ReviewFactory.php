<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reviewer_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'reviewee_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'comment' => $this->faker->sentence($nbWords = 15, $variableNbWords = true),
            'date' => $this->faker->dateTimeBetween('-5 years', 'now'),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Repo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Repo>
 */
class RepoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => $this->faker->sentence(),
            'url' => $this->faker->url(),
            'git_id' => (string) $this->faker->unique()->numberBetween(1000000,9999999),
        ];
    }
}

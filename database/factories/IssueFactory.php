<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\Repo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->word() . ' ' . 'Issue',
            'repo_id' => Repo::factory(),
            'url' => $this->faker->url(),
            'description' => $this->faker->sentence(),
        ];
    }
}

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
            'repo_id' => Repo::factory(),
            'url' => $this->faker->url(),
            'description' => $this->faker->sentence(),
            'git_id' => (string) $this->faker->unique()->numberBetween(1000000, 9999999),
            'provider' => 'github',
        ];
    }

    public function gitlab(): self
    {
        return $this->state(fn () => ['provider' => 'gitlab']);
    }

    public function bitbucket(): self
    {
        return $this->state(fn () => ['provider' => 'bitbucket']);
    }
}

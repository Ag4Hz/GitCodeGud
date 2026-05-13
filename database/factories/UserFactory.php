<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'nickname' => fake()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'oauth_provider' => 'github',
            'oauth_provider_id' => fake()->numberBetween(1, 999999),
            'xp' => 200,
            'remember_token' => Str::random(10),
            'role' => 'user',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user without GitHub OAuth (no avatar).
     */
    public function withoutGitHub(): static
    {
        return $this->state(fn (array $attributes) => [
            'oauth_provider' => null,
            'oauth_provider_id' => null,
        ]);
    }

    /**
     * Create a user with a specific GitHub username for realistic avatar.
     */
    public function withGitHubUsername(string $username): static
    {
        return $this->state(fn (array $attributes) => [
            'nickname' => $username,
            'oauth_provider' => 'github',
            'oauth_provider_id' => fake()->numerify('#########'),
        ]);
    }
}

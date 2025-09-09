<?php

namespace Database\Factories;

use App\Models\Bounty;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $owner = $this->faker->userName;
        $repo = $this->faker->slug(2);
        $prNumber = $this->faker->numberBetween(1, 9999);

        return [
            'bounty_id' => Bounty::factory(),
            'user_id' => User::factory(),
            'pr_url' => "https://github.com/{$owner}/{$repo}/pull/{$prNumber}",
            'status' => $this->faker->randomElement(['pending', 'accepted', 'rejected']),
        ];
    }
}

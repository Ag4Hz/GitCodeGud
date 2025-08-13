<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Badge>
 */
class BadgeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adjectives = ['Expert', 'Master', 'Pro', 'Champion', 'Ninja', 'Wizard', 'Guru', 'Legend',
            'Hero', 'Elite', 'Ace', 'Specialist', 'Commander', 'Senior', 'Chief', 'Prime'];
        $skills = ['Code', 'Bug', 'Team', 'Performance', 'Security', 'API', 'Database', 'Frontend',
            'Backend', 'Testing', 'DevOps', 'Design', 'Mobile', 'Cloud', 'AI', 'Data'];

        return [
            'name' => $this->faker->randomElement($skills) . ' ' . $this->faker->randomElement($adjectives),
            'description' => $this->faker->sentence(8, true),
        ];
    }
}

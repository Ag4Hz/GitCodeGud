<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Skill>
 */
class SkillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $skills = [
            'PHP', 'Javascript', 'Python', 'Java', 'C#', 'C++', 'Vue.js', 'React', 'Laravel', 'MongoDB', 'Linux', 'Git',
        ];
        return [
            'skill_name' => fake()->unique()->randomElement($skills),
        ];
    }
}

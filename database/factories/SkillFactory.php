<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Skill>
 */
class SkillFactory extends Factory
{
    public static array $skills = [
        ['skill_name' => 'PHP', 'type' => 'language'],
        ['skill_name' => 'JavaScript', 'type' => 'language'],
        ['skill_name' => 'Python', 'type' => 'language'],
        ['skill_name' => 'Java', 'type' => 'language'],
        ['skill_name' => 'C#', 'type' => 'language'],
        ['skill_name' => 'C++', 'type' => 'language'],
        ['skill_name' => 'Vue', 'type' => 'framework'],
        ['skill_name' => 'React', 'type' => 'framework'],
        ['skill_name' => 'Laravel', 'type' => 'framework'],
        ['skill_name' => 'MongoDB', 'type' => 'database'],
        ['skill_name' => 'Linux', 'type' => 'tool'],
        // Insert here more skills if needed
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return fake()->randomElement(self::$skills);
    }

    public static function skillCount()
    {
        return count(self::$skills);
    }
}

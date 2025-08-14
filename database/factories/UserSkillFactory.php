<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Skill;
use App\Models\UserSkill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSkill>
 */
class UserSkillFactory extends Factory
{
    protected $model = UserSkill::class;

    public function definition(): array
    {
        $xp = fake()->numberBetween(0, 500000);

        return [
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'skill_id' => Skill::inRandomOrder()->first()?->id ?? Skill::factory(),
            'xp' => $xp,
            'level' => $this->calculateLevel($xp),
        ];
    }

    private function calculateLevel(int $xp): int
    {
        if ($xp < 1000) return 1;
        if ($xp < 5000) return 2;
        if ($xp < 15000) return 3;
        if ($xp < 30000) return 4;
        if ($xp < 60000) return 5;
        if ($xp < 120000) return 6;
        if ($xp < 250000) return 7;
        if ($xp < 400000) return 8;
        return 9;
    }
}

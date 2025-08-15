<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseSkillCount = min(rand(8, 12), 12);
        Skill::factory($baseSkillCount)->create();

        $currentCount = Skill::count();
        $maxSkills = 12;

        if ($currentCount < $maxSkills && rand(1, 100) <= 60) {
            $additionalCount = min(rand(1, 3), $maxSkills - $currentCount);
            if ($additionalCount > 0) {
                Skill::factory($additionalCount)->create();
            }
        }
    }
}

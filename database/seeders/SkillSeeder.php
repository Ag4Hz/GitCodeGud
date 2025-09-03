<?php

namespace Database\Seeders;

use App\Models\Skill;
use Database\Factories\SkillFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach(SkillFactory::$skills as $skill) {
            Skill::firstOrCreate($skill);
        }
    }
}

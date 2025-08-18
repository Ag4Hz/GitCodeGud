<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Skill;
use App\Models\SkillUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SkillUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $skills = Skill::all();

        if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::all();
        }

        if ($skills->isEmpty()) {
            $this->call(SkillSeeder::class);
            return;
        }

        foreach ($users as $user) {
            if (rand(1, 100) <= 10) continue;

            $skillCount = rand(1, min(20, $skills->count()));
            $randomSkills = $skills->random($skillCount);

            foreach ($randomSkills as $skill) {
                $randomXp = rand(1, 100);

                if ($randomXp <= 40) {
                    $xp = rand(0, 5000);
                } elseif ($randomXp <= 70) {
                    $xp = rand(5000, 50000);
                } elseif ($randomXp <= 90) {
                    $xp = rand(50000, 200000);
                } else {
                    $xp = rand(200000, 500000);
                }

                SkillUser::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'skill_id' => $skill->id,
                    ],
                    [
                        'xp' => $xp,
                        'level' => $this->calculateLevel($xp),
                    ]
                );
            }
        }

        $extraRelations = rand(100, 500);
        $createdRelations = 0;
        $maxAttempts = $extraRelations * 3;

        for ($i = 0; $i < $maxAttempts && $createdRelations < $extraRelations; $i++) {
            $userId = $users->random()->id;
            $skillId = $skills->random()->id;

            if (!SkillUser::where('user_id', $userId)->where('skill_id', $skillId)->exists()) {
                $xp = rand(0, 500000);

                SkillUser::create([
                    'user_id' => $userId,
                    'skill_id' => $skillId,
                    'xp' => $xp,
                    'level' => $this->calculateLevel($xp),
                ]);

                $createdRelations++;
            }
        }
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

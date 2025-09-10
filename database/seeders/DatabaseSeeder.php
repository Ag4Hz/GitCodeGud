<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BountySeeder::class,
            UserSeeder::class,
            SkillSeeder::class,
            BadgeSeeder::class,
        ]);

        $this->call([
            SkillUserSeeder::class,
            BadgeUserSeeder::class,
        ]);

        $this->call([
            SubmissionSeeder::class,
            ReviewSeeder::class,
            FollowerSeeder::class,
        ]);
    }
}

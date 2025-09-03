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
            ReviewSeeder::class,
        ]);

        $this->call([
            SkillUserSeeder::class,
            BadgeUserSeeder::class,
        ]);

        $this->call([
            SubmissionSeeder::class,
            FollowerSeeder::class,
        ]);
    }
}

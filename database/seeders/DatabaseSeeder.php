<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
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
            RepoSeeder::class,
            IssueSeeder::class,
            BountySeeder::class,
            SubmissionSeeder::class,
            FollowerSeeder::class,
        ]);
    }
}

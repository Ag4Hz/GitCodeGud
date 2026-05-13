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
            SkillUserSeeder::class,
            BadgeUserSeeder::class,
            SubmissionSeeder::class,
            ReviewSeeder::class,
            FollowerSeeder::class,
            UserProviderSeeder::class,
            OrganizationSeeder::class,
        ]);
    }
}

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
            UserSkillSeeder::class,
            UserBadgeSeeder::class,
        ]);
    }
}

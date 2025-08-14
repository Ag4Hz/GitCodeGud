<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badgeCount = rand(50, 150);
        for ($i = 0; $i < $badgeCount; $i++) {
            $randomType = rand(1, 100);

            if ($randomType <= 30) {
                Badge::factory()->create();
            } else {
                $name = fake()->word();

                Badge::firstOrCreate(
                    ['name' => $name],
                    ['description' => fake()->sentence(rand(1, 10))]
                );
            }
        }

        if (rand(1, 100) <= 50) {
            Badge::factory(rand(10, 50))->create();
        }

        if (rand(1, 100) <= 30) {
            for ($j = 0; $j < rand(5, 20); $j++) {
                Badge::firstOrCreate(
                    ['name' => fake()->randomLetter() . rand(1, 999)],
                    ['description' => fake()->words(rand(1, 15), true)]
                );
            }
        }
    }
}

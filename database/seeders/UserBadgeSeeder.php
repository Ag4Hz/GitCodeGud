<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Badge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserBadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $badges = Badge::all();

        if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::all();
        }

        if ($badges->isEmpty()) {
            $this->call(BadgeSeeder::class);
            $badges = Badge::all();
        }

        $userBadgeData = [];
        $existingCombinations = [];

        foreach ($users as $user) {
            $badgeCount = rand(0, 10);

            if ($badgeCount > 0) {
                $randomBadges = $badges->random(min($badgeCount, $badges->count()));

                foreach ($randomBadges as $badge) {
                    $key = "{$user->id}-{$badge->id}";

                    if (!isset($existingCombinations[$key])) {
                        $userBadgeData[] = [
                            'user_id' => $user->id,
                            'badge_id' => $badge->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $existingCombinations[$key] = true;
                    }
                }
            }
        }
    }
}

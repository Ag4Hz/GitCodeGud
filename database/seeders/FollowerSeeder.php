<?php

namespace Database\Seeders;

use App\Models\Follower;
use App\Models\User;
use Illuminate\Database\Seeder;

class FollowerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        if ($users->isEmpty()) {
            $users = User::factory(50)->create();
        }

        foreach ($users as $user) {
            $others = $users->where('id', '!=', $user->id);

            if ($others->isEmpty()) {
                continue;
            }

            $targets = $others->random(min(rand(1, 5), $others->count()));

            foreach ($targets as $target) {
                Follower::query()->firstOrCreate([
                    'follower_id' => $user->id,
                    'followed_id' => $target->id,
                ]);
            }
        }
    }
}

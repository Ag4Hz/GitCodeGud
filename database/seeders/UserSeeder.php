<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userCount = rand(80, 150);

        $users = User::factory($userCount)->create();
        $topUsers = $users->random(rand(5, 15));
        foreach ($topUsers as $user) {
            $user->update(['xp' => rand(10000, 500000)]);
        }

        $beginners = $users->random(rand(10, 25));
        foreach ($beginners as $user) {
            $user->update(['xp' => rand(0, 5000)]);
        }
    }
}

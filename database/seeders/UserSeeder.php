<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userCount = rand(80, 150);

        User::factory($userCount)->create();
        $topUsers = User::all()->random(rand(5, 15));
        foreach ($topUsers as $user) {
            $user->update(['xp' => rand(10000, 500000)]);
        }

        $beginners = User::all()->random(rand(10, 25));
        foreach ($beginners as $user) {
            $user->update(['xp' => rand(0, 5000)]);
        }
    }
}

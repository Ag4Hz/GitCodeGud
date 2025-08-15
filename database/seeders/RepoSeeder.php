<?php

namespace Database\Seeders;

use App\Models\Repo;
use App\Models\User;
use Illuminate\Database\Seeder;

class RepoSeeder extends Seeder
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
            Repo::factory(rand(1, 3))->create([
                'user_id' => $user->id
            ]);
        }
    }


}

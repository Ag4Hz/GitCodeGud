<?php

namespace Database\Seeders;

use App\Models\Issue;
use App\Models\Repo;
use Illuminate\Database\Seeder;

class IssueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $repos = Repo::all();

        if ($repos->isEmpty()) {
            $this->call(RepoSeeder::class);
            $repos = Repo::all();
        }

        foreach ($repos as $repo) {
            Issue::factory(rand(1, 3))->create([
                'repo_id' => $repo->id
            ]);
        }
    }
}

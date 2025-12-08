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
            $issueCount = rand(1, 3);

            for ($i = 0; $i < $issueCount; $i++) {
                $random = rand(1, 3);
                $provider = match($random) {
                    1 => 'github',
                    2 => 'gitlab',
                    3 => 'bitbucket',
                };

                $issueNumber = rand(1, 999);
                $url = match($provider) {
                    'github' => "https://github.com/{$repo->git_id}/issues/{$issueNumber}",
                    'gitlab' => "https://gitlab.com/{$repo->git_id}/-/issues/{$issueNumber}",
                    'bitbucket' => "https://bitbucket.org/{$repo->git_id}/issues/{$issueNumber}",
                };

                Issue::factory()->create([
                    'repo_id' => $repo->id,
                    'provider' => $provider,
                    'url' => $url,
                ]);
            }
        }
    }
}

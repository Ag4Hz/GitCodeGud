<?php

namespace Database\Seeders;

use App\Models\Repo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class RepoSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = Faker::create();
    }

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
            $repoCount = rand(1, 3);

            for ($i = 0; $i < $repoCount; $i++) {
                $random = rand(1, 3);
                $provider = match($random) {
                    1 => 'github',
                    2 => 'gitlab',
                    3 => 'bitbucket',
                };

                $repoName = strtolower($this->faker->word() . '-' . $this->faker->word());
                $username = $user->nickname ?? 'user' . $user->id;
                $gitId = "{$username}/{$repoName}";

                $url = match($provider) {
                    'github' => "https://github.com/{$gitId}",
                    'gitlab' => "https://gitlab.com/{$gitId}",
                    'bitbucket' => "https://bitbucket.org/{$gitId}",
                };

                Repo::factory()->create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'git_id' => $gitId,
                    'url' => $url,
                ]);
            }
        }
    }


}

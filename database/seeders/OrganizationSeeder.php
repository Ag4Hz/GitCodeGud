<?php

namespace Database\Seeders;

use App\Models\Bounty;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Repo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        $providers = ['github', 'gitlab', 'bitbucket'];
        $orgCount = rand(5, 10);

        for ($i = 0; $i < $orgCount; $i++) {
            $owner = $users->random();
            $provider = $providers[array_rand($providers)];

            $name = ucfirst(fake()->word()) . ' ' . ucfirst(fake()->word());
            $slug = Str::slug($name) . '-' . Str::random(4);

            $repoField = $provider . '_repo';
            $repoValue = strtolower(fake()->word() . '-' . fake()->word());

            $org = Organization::create([
                'name'      => $name,
                'slug'      => $slug,
                'owner_id'  => $owner->id,
                $repoField  => $repoValue,
            ]);

            $org->members()->attach($owner->id, [
                'role'      => 'owner',
                'joined_at' => now(),
            ]);

            $members = $users->except([$owner->id])->random(min(rand(2, 6), $users->count() - 1));
            foreach ($members as $member) {
                $org->members()->attach($member->id, [
                    'role'      => 'member',
                    'joined_at' => fake()->dateTimeBetween('-6 months', 'now'),
                ]);
            }

            $bountyCount = rand(2, 5);
            for ($j = 0; $j < $bountyCount; $j++) {

                $repo = Repo::where('provider', $provider)->inRandomOrder()->first();

                if (!$repo) {
                    $repoName = strtolower(fake()->word() . '-' . fake()->word());
                    $username  = $owner->nickname ?? 'user' . $owner->id;
                    $gitId     = "{$username}/{$repoName}";
                    $url = match ($provider) {
                        'github'    => "https://github.com/{$gitId}",
                        'gitlab'    => "https://gitlab.com/{$gitId}",
                        'bitbucket' => "https://bitbucket.org/{$gitId}",
                    };

                    $repo = Repo::create([
                        'user_id'     => $owner->id,
                        'provider'    => $provider,
                        'git_id'      => $gitId,
                        'url'         => $url,
                        'description' => fake()->sentence(),
                    ]);
                }

                $issueNumber = rand(1, 999);
                $gitId       = $repo->git_id;
                $issueUrl    = match ($provider) {
                    'github'    => "https://github.com/{$gitId}/issues/{$issueNumber}",
                    'gitlab'    => "https://gitlab.com/{$gitId}/-/issues/{$issueNumber}",
                    'bitbucket' => "https://bitbucket.org/{$gitId}/issues/{$issueNumber}",
                };

                $issue = Issue::create([
                    'repo_id'     => $repo->id,
                    'provider'    => $provider,
                    'url'         => $issueUrl,
                    'git_id'      => (string) fake()->unique()->numberBetween(1000000, 9999999),
                    'description' => fake()->sentence(),
                ]);

                Bounty::create([
                    'issue_id'        => $issue->id,
                    'organization_id' => $org->id,
                    'status'          => fake()->randomElement(['open', 'open', 'open', 'closed']),
                    'title'           => 'Fix ' . fake()->word() . ' ' . fake()->word(),
                    'description'     => fake()->sentence(),
                    'reward_xp'       => fake()->numberBetween(5, 100),
                    'languages'       => fake()->randomElements(
                        ['PHP', 'JavaScript', 'TypeScript', 'Python', 'Java', 'Go', 'Rust', 'Vue', 'React', 'Laravel'],
                        fake()->numberBetween(1, 3)
                    ),
                ]);
            }
        }
    }
}

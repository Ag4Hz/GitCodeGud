<?php

namespace Database\Seeders;

use App\Models\Bounty;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubmissionSeeder extends Seeder
{
    public function run(): void
    {
        $bounties = Bounty::whereDoesntHave('submissions')->pluck('id');

        if ($bounties->isEmpty()) {
            return;
        }

        $userIds = User::pluck('id');

        if ($userIds->isEmpty()) {
            $userIds = User::factory(10)->create()->pluck('id');
        }

        $submissions = $bounties->flatMap(function ($bountyId) use ($userIds) {
            $submissionCount = rand(1, 3);
            $selectedUserIds = $userIds->random(min($submissionCount, $userIds->count()));

            return $selectedUserIds->map(function ($userId) use ($bountyId) {
                $provider = fake()->randomElement(['github', 'gitlab', 'bitbucket']);
                $owner = fake()->userName();
                $repo = fake()->word() . '-' . fake()->word();
                $prNumber = fake()->numberBetween(1, 999);

                $prUrl = match ($provider) {
                    'github' => "https://github.com/{$owner}/{$repo}/pull/{$prNumber}",
                    'gitlab' => "https://gitlab.com/{$owner}/{$repo}/-/merge_requests/{$prNumber}",
                    'bitbucket' => "https://bitbucket.org/{$owner}/{$repo}/pull-requests/{$prNumber}",
                };

                return [
                    'bounty_id' => $bountyId,
                    'user_id' => $userId,
                    'pr_url' => $prUrl,
                    'status' => fake()->randomElement(['pending', 'accepted', 'rejected']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });
        })->toArray();

        Submission::insert($submissions);
    }
}

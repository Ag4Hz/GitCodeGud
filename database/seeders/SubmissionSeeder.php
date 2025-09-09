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

            return $selectedUserIds->map(fn($userId) => [
                'bounty_id' => $bountyId,
                'user_id' => $userId,
                'pr_url' => fake()->url(),
                'status' => fake()->randomElement(['pending', 'accepted', 'rejected']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        })->toArray();

        Submission::insert($submissions);
    }
}

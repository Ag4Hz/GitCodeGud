<?php

namespace Database\Seeders;

use App\Models\Bounty;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bounties = Bounty::with('issue.repo')->get();

        if ($bounties->isEmpty()) {
            $this->command->info('No bounties found. Please run BountySeeder first.');
            return;
        }

        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->info('No users found. Creating some users first.');
            $users = User::factory(10)->create();
        }

        foreach ($bounties as $bounty) {
            if ($bounty->submissions()->count() === 0) {
                $submissionCount = rand(1, 3);
                $selectedUsers = $users->random(min($submissionCount, $users->count()));

                foreach ($selectedUsers as $user) {
                    Submission::factory()->create([
                        'bounty_id' => $bounty->id,
                        'user_id' => $user->id,
                    ]);
                }
            }
        }
    }
}

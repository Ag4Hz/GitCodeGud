<?php

namespace Database\Seeders;

use App\Models\Bounty;
use App\Models\Issue;
use Illuminate\Database\Seeder;

class BountySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $issues = Issue::all();

        if ($issues->isEmpty()) {
            $this->call(IssueSeeder::class);
            $issues = Issue::all();
        }

        foreach ($issues as $issue) {
            if (!$issue->bounty) {
                Bounty::factory()->create([
                    'issue_id' => $issue->id
                ]);
            }
        }

    }
}

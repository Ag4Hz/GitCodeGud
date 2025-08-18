<?php

namespace Database\Seeders;

use App\Models\Bounty;
use App\Models\Submission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubmissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $bounties = Bounty::all();

        if ($bounties->isEmpty()) {
            $this->call(BountySeeder::class);
            $bounties = Bounty::all();
        }

        foreach ($bounties as $bounty) {
            Submission::factory(rand(1, 3))->create([
                'bounty_id' => $bounty->id
            ]);
        }
    }
}

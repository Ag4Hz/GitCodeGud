<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Review;
use App\Models\UsersReview;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersReviewSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        if ($users->isEmpty()) {
            $this->call(UserSeeder::class);
            $users = User::all();
        }

        if ($reviews->isEmpty()) {
            $this->call(ReviewSeeder::class);
            $reviews = Review::all();
        }

        foreach ($reviews as $review) {
            $reviewer = $users->random();
            $reviewee = $users->where('id', '!=', $reviewer->id)->random();

            UsersReview::firstOrCreate([
                'review_id' => $review->id,
                'reviewer_id' => $reviewer->id,
                'reviewee_id' => $reviewee->id,
            ]);
        }

        $extraCount = rand(100, 300);
        for ($i = 0; $i < $extraCount; $i++) {
            $reviewer = $users->random();
            $reviewee = $users->where('id', '!=', $reviewer->id)->random();
            $review = Review::factory()->create();

            UsersReview::firstOrCreate([
                'review_id' => $review->id,
                'reviewer_id' => $reviewer->id,
                'reviewee_id' => $reviewee->id,
            ]);
        }

        if (rand(1, 100) <= 50) {
            UsersReview::factory(rand(50, 150))->create();
        }
    }
}

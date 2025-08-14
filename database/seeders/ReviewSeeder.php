<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->count() <= 1) {
            $this->call(UserSeeder::class);
            $users = User::all();
        }

        $reviewCount = rand(50, 200);

        for ($i = 0; $i < $reviewCount; $i++) {
            $reviewer = $users->random();
            $reviewee = $users->where('id', '!=', $reviewer->id)->random();

            Review::create([
                'reviewer_id' => $reviewer->id,
                'reviewee_id' => $reviewee->id,
                'comment' => fake()->sentence(rand(5, 20), true),
                'date' => fake()->dateTimeBetween('-5 years', 'now'),
            ]);
        }

        $reviews = Review::all();

        $oldReviews = $reviews->random(rand(5, 15));
        foreach ($oldReviews as $review) {
            $review->update([
                'date' => fake()->dateTimeBetween('-3 years', '-1 year')
            ]);
        }

        $recentReviews = $reviews->random(rand(10, 25));
        foreach ($recentReviews as $review) {
            $review->update([
                'date' => fake()->dateTimeBetween('-1 week', 'now')
            ]);
        }

        $longReviews = $reviews->random(rand(5, 10));
        foreach ($longReviews as $review) {
            $review->update([
                'comment' => fake()->paragraph(rand(3, 8))
            ]);
        }

        $shortReviews = $reviews->random(rand(8, 15));
        foreach ($shortReviews as $review) {
            $review->update([
                'comment' => fake()->sentence(rand(3, 6))
            ]);
        }
    }
}

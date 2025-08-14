<?php

namespace Database\Seeders;

use App\Models\Review;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviewCount = rand(50, 200);
        Review::factory($reviewCount)->create();
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

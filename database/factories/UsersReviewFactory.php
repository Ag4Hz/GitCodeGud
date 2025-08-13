<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Review;
use App\Models\UsersReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UsersReview>
 */
class UsersReviewFactory extends Factory
{
    protected $model = UsersReview::class;

    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'reviewer_id' => User::factory(),
            'reviewee_id' => User::factory(),
        ];
    }
}

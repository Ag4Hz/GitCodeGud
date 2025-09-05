<?php


namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Services\ReviewService;

class CanReviewUser implements ValidationRule
{
    private ReviewService $reviewService;

    public function __construct()
    {
        $this->reviewService = app(ReviewService::class);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->reviewService->canUserReview(User::find($value))) {
            $fail('You can only review users who have a submission relationship with you (either direction).');
        }
    }
}


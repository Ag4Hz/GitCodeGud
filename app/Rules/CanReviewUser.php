<?php


namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Services\ReviewService;

class CanReviewUser implements ValidationRule
{
    public function __construct(
        private ReviewService $reviewService
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->reviewService->canUserReview(User::find($value))) {
            $fail('You can only review users who have a submission relationship with you (either direction).');
        }
    }
}


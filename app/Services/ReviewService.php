<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ReviewService
{
    public function getUserReviews(User $user): LengthAwarePaginator
    {
        return $user->reviewsReceived()
            ->with(['reviewer'])
            ->latest()
            ->paginate(7);

    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use App\Services\ReviewService;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService,
    ) {}
    public function store(StoreReviewRequest $request)
    {

        $review = $this->reviewService->createReview($request->integer('reviewee_id'), $request->string('comment'), $request->integer('rating') );


        return redirect()
            ->route('users.show', $request->integer('reviewee_id'));
    }
}

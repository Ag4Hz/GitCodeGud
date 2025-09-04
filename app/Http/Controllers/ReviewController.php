<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{

    public function store(StoreReviewRequest $request)
    {
        Review::create([
            'user_id'     => Auth::id(),
            'reviewee_id' => $request->integer('reviewee_id'),
            'comment'     => $request->string('comment'),
            'date'        => now(),
        ]);

        return redirect()
            ->route('users.show', $request->integer('reviewee_id'))
            ->with('success', 'Review created successfully!');
    }
}

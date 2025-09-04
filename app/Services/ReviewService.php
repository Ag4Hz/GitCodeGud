<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Submission;

class ReviewService
{
    public function canUserReview(User $profileUser): bool
    {
        $viewerId = Auth::id();

        if (!$viewerId || $viewerId === $profileUser->id) {
            return false;
        }

        return $this->hasSubmissionBetweenUsers($viewerId, $profileUser->id);
    }

    public function hasSubmissionBetweenUsers(int $firstUserId, int $secondUserId): bool
    {
        return Submission::query()
            ->join('bounties', 'submissions.bounty_id', '=', 'bounties.id')
            ->join('issues', 'bounties.issue_id', '=', 'issues.id')
            ->join('repos', 'issues.repo_id', '=', 'repos.id')
            ->where(function ($subquery) use ($firstUserId, $secondUserId) {
                $subquery->where('submissions.user_id', '=', $firstUserId)
                    ->where('repos.user_id', '=', $secondUserId);
            })
            ->orWhere(function ($subquery) use ($firstUserId, $secondUserId) {
                $subquery->where('submissions.user_id', '=', $secondUserId)
                    ->where('repos.user_id', '=', $firstUserId);
            })
            ->exists();
    }

    public function getUserReviews(User $user): LengthAwarePaginator
    {
        return $user->reviewsReceived()
            ->with(['reviewer'])
            ->latest()
            ->paginate(7);

    }
}
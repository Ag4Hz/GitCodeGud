<?php

namespace App\Services;

use App\Models\Bounty;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserBountyService
{
    public function getUserBountiesWithDeleted(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Bounty::with(['issue.repo', 'submissions'])
            ->whereHas('issue.repo', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->withTrashed()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}

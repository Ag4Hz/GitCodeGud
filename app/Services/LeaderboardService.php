<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class LeaderboardService
{
    public function getLeaderboard(string $by, string $dir): LengthAwarePaginator
    {
        $by = 'xp';
        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';

        $query = User::query()
            ->select('users.*')
            ->orderBy('users.xp', $dir)
            ->orderBy('users.id');

        $paginator = $query
            ->paginate((int)config('leaderboard.per_page', 10))
            ->withQueryString();

        $start = ($paginator->currentPage() - 1) * $paginator->perPage();

        return $paginator->through(function ($user, $i) use ($start) {
            $user->setAttribute('rank', $start + $i + 1);
            return $user;
        });
    }
}

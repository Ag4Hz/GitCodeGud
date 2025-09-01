<?php


namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class LeaderboardService
{
    public function getLeaderboard(?int $page = null, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('leaderboard.per_page', 10);
        $page = $page ?? (Paginator::resolveCurrentPage() ?: 1);
        $start = ($page - 1) * $perPage;

        return User::query()
            ->orderByDesc('xp')
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString()
            ->through(function ($user, $i) use ($start) {
                return [
                    'id' => $user->id,
                    'nickname' => $user->nickname,
                    'avatar' => $user->avatar,
                    'name' => $user->name,
                    'xp' => $user->xp,
                    'rank' => $start + $i + 1
                ];
            });
    }

}

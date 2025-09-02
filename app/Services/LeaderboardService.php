<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class LeaderboardService
{
    public function getLeaderboard(string $by, string $dir): LengthAwarePaginator
    {
        $perPage = config('leaderboard.per_page', 10);
        $page    = Paginator::resolveCurrentPage() ?: 1;

        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';

        return User::query()
            ->join('user_skills', 'user_skills.user_id', '=', 'users.id')
            ->join('skills', 'skills.id', '=', 'user_skills.skill_id')
            ->select('users.*', 'skills.*', 'user_skills.*')
            ->orderBy('users.' . ($by === 'xp' ? 'xp' : 'id'), $dir)
            ->orderBy('skills.type', $dir)
            ->orderBy('skills.skill_name', $dir)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();

    }
}

<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    public function __construct(private LeaderboardService $leaderboard)
    {
    }

    public function index(Request $request)
    {
        $byParam = strtolower($request->string('by')->toString());
        $allowedBy = ['xp', 'level', 'skill_xp'];
        $by = in_array($byParam, $allowedBy, true) ? $byParam : 'xp';

        $dirParam = strtolower($request->string('dir')->toString());
        $dir = $dirParam === 'asc' ? 'asc' : 'desc';

        return Inertia::render('Leaderboard', [
            'leaderboardUsers' => $this->leaderboard->getLeaderboard($by, $dir),
            'userFilters' => ['search' => $request->string('search_user')->toString()],
            'users' => UserService::searchUser(
                UserService::listUser($request->string('search_user')->toString())
            )['users'],
            'sort' => ['by' => $by, 'dir' => $dir],
        ]);
    }
}

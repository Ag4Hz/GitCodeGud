<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    public function __construct(private LeaderboardService $leaderboard) {}

    public function index(Request $request)
    {
        return Inertia::render('Leaderboard', [
            'leaderboardUsers' => $this->leaderboard->getLeaderboard('level', 'asc'),
            'userFilters'      => ['search' => $request->string('search_user')->toString()],
            'users'            => UserService::searchUser(
                UserService::listUser($request->string('search_user')->toString())
            )['users'],
        ]);
    }
}

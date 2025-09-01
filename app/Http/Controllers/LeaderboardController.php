<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    public function __construct(private LeaderboardService $leaderboard) {}

    public function index()
    {
        return Inertia::render('Leaderboard', [
            'users' => $this->leaderboard->getLeaderboard(),
        ]);
    }
}

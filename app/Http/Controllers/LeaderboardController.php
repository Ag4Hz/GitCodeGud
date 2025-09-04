<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $leaderboardService
    ) {}

    public function index(Request $request): Response
    {
        $data = $this->leaderboardService->getLeaderboardPageData($request);

        return Inertia::render('Leaderboard', $data);
    }
}

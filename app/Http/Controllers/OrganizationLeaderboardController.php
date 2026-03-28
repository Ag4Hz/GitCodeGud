<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationLeaderboardController extends Controller
{
    public function __construct(
        private readonly LeaderboardService $leaderboardService
    ) {}

    public function index(Request $request, Organization $organization): Response
    {
        $this->authorize('view', $organization);
        $data = $this->leaderboardService->getLeaderboardPageData($request, $organization->id);
        return Inertia::render('Organizations/Leaderboard', array_merge($data, [
            'organization' => $organization,
        ]));
    }
}

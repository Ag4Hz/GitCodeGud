<?php

namespace App\Http\Controllers;

use App\Services\BountySearchService;
use App\Models\Bounty;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\UserService;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $bountySearchService = new BountySearchService();
        $bountySearchData = $bountySearchService->getBountyData($request);

        $searchTerm = $request->string('search_user');
        $users = UserService::listUser($searchTerm);
        $userSearchData = UserService::searchUser($users);

        $combinedProps = array_merge($bountySearchData, [
            'userFilters' => ['search' => $searchTerm->toString()],
            'users' => $userSearchData['users'] ?? ['data' => []],
        ]);

        return Inertia::render('Dashboard', $combinedProps);
    }
}

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
        $term = $request->string('search');
      
        $users = UserService::listUser($term);
        $userProps = UserService::searchUser($users);

        $combinedProps = array_merge($bountySearchData, [
            'userFilters' => ['search' => $term->toString()],
            'users' => $userProps['results'] ?? ['data' => []],
        ]);

        return Inertia::render('Dashboard', $combinedProps);
    }
}
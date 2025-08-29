<?php

namespace App\Http\Controllers;

use App\Services\BountySearchService;
use App\Models\Bounty;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\UserService;
use App\Services\PopularBountiesService;

class DashboardController extends Controller
{
    public function __construct(
        protected PopularBountiesService $popularBountiesService
    ) {}
    public function index(Request $request): Response
    {
        $bountySearchService = new BountySearchService();
        $bountySearchData = $bountySearchService->getBountyData($request);

        $userSearchTerm = $request->string('search_user');
        $users = UserService::listUser($userSearchTerm);
        $userSearchData = UserService::searchUser($users);

        $combinedProps = array_merge($bountySearchData, [
            'userFilters' => ['search' => $userSearchTerm->toString()],
            'users' => $userSearchData['users'] ?? ['data' => []],
            'popularBounties' => $this->popularBountiesService->getPopularBounties(10),
            'trendingBounties' => $this->popularBountiesService->getTrendingBounties(5),
        ]);

        return Inertia::render('Dashboard', $combinedProps);
    }
}

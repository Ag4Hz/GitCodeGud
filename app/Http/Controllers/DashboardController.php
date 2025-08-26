<?php

namespace App\Http\Controllers;

use App\Services\BountySearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $bountySearchService = new BountySearchService();
        $bountySearchData = $bountySearchService->getBountyData($request);

        return Inertia::render('Dashboard', $bountySearchData);
    }
}

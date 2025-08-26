<?php

namespace App\Http\Controllers;

use App\Http\Resources\BountyResource;
use App\Models\Bounty;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Bounty::with(['issue.repo'])
            ->active()
            ->where('status', 'open')
            ->latest();

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = strtolower($request->get('search'));
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchTerm}%"])
                    ->orWhereHas('issue.repo', function ($repo) use ($searchTerm) {
                        $repo->whereRaw('LOWER(git_id) LIKE ?', ["%{$searchTerm}%"]);
                    });
            });
        }

        // Language filtering
        if ($request->filled('language')) {
            $language = $request->get('language');
            $query->whereJsonContains('languages', $language);
        }

        $bounties = $query->paginate(12)->withQueryString();
        $availableLanguages = Bounty::getAvailableLanguages();

        return Inertia::render('Dashboard', [
            'bounties' => $bounties,
            'availableLanguages' => $availableLanguages,
            'filters' => [
                'search' => $request->get('search', ''),
                'language' => $request->get('language', ''),
            ],
        ]);
    }
}

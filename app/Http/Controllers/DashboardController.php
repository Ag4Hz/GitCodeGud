<?php

namespace App\Http\Controllers;
use App\Models\Bounty;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\UserService;
class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Bounty::with(['issue.repo'])
            ->active()
            ->where('status', 'open')
            ->latest();

        // Search functionality
        $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = strtolower($request->get('search'));
            return $q->where(function ($query) use ($searchTerm) {
                $query->whereRaw('LOWER(title) LIKE ?', ["%{$searchTerm}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchTerm}%"])
                    ->orWhereHas('issue.repo', function ($repo) use ($searchTerm) {
                        $repo->whereRaw('LOWER(git_id) LIKE ?', ["%{$searchTerm}%"]);
                    });
            });
        });

        // Language filtering
        $query->when($request->filled('language'), function ($q) use ($request) {
            return $q->whereJsonContains('languages', $request->get('language'));
        });

        $bounties = $query->paginate(12)->withQueryString();
        $availableLanguages = Bounty::getAvailableLanguages();

        $term = $request->string('search_user');
        $users = UserService::listUser($term);

        return Inertia::render('Dashboard', [
            'bounties' => $bounties,
            'availableLanguages' => $availableLanguages,
            'filters' => [
                'search' => $request->get('search', ''),
                'language' => $request->get('language', ''),
            ],
            'userFilters' => [
                'search' => $term,
            ],
            'users' => $users,
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Bounty;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BountySearchService
{
    /**
     * Get complete bounty search data for controllers
     */
    public function getBountyData(Request $request, int $perPage = 12): array
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

        $bounties = $query->paginate($perPage)->withQueryString();
        $availableLanguages = Bounty::getAvailableLanguages();

        return [
            'bounties' => $bounties,
            'availableLanguages' => $availableLanguages,
            'filters' => [
                'search' => $request->get('search', ''),
                'language' => $request->get('language', ''),
            ],
        ];
    }
}

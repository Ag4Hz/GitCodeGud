<?php

namespace App\Services;

use App\Models\Bounty;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BountySearchService
{
    public function buildBountyQuery(): Builder
    {
        return Bounty::with(['issue.repo'])
            ->active()
            ->where('status', 'open')
            ->latest();
    }

    public function applySearchFilter(Builder $query, Request $request): Builder
    {
        return $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = $request->get('search');
            return $q->where(function ($query) use ($searchTerm) {
                $query->where('title', 'ILIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'ILIKE', "%{$searchTerm}%")
                    ->orWhereHas('issue.repo', function ($repo) use ($searchTerm) {
                        $repo->where('git_id', 'ILIKE', "%{$searchTerm}%");
                    });
            });
        });
    }

    public function applyLanguageFilter(Builder $query, Request $request): Builder
    {
        return $query->when($request->filled('language'), function ($q) use ($request) {
            $language = $request->get('language');
            return $q->whereJsonContains('languages', $language);
        });
    }

    public function applyProviderFilter(Builder $query, Request $request): Builder
    {
        return $query->when($request->filled('provider'), function ($q) use ($request) {
            $provider = $request->get('provider');

            return $q->whereHas('issue', function ($issueQuery) use ($provider) {
                $issueQuery->where('provider', $provider);
            });
        });
    }


//    public function getPaginatedBounties(Builder $query, int $perPage = 12): LengthAwarePaginator
//    {
//        return $query->paginate($perPage)->withQueryString();
//    }

    public function getPaginatedBounties(Builder $query, int $perPage = 12): LengthAwarePaginator
    {
        $paginated = $query->paginate($perPage)->withQueryString();

        // Add provider to each bounty's issue
        $paginated->getCollection()->transform(function ($bounty) {
            return $bounty;
        });

        return $paginated;
    }


    public function getBountyData(Request $request, int $perPage = 12): array
    {
        $query = $this->buildBountyQuery();
        $query = $this->applySearchFilter($query, $request);
        $query = $this->applyLanguageFilter($query, $request);
        $query = $this->applyProviderFilter($query, $request);

        return [
            'bounties' => $this->getPaginatedBounties($query, $perPage),
            'availableLanguages' => Bounty::getAvailableLanguages(),
            'availableProviders' => Issue::getAvailableProviders(),
            'filters' => [
                'search' => $request->get('search', ''),
                'language' => $request->get('language', ''),
                'provider' => $request->get('provider', ''),
            ],
        ];
    }
}

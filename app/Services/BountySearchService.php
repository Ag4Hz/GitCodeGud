<?php

namespace App\Services;

use App\Models\Bounty;
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

    public function getPaginatedBounties(Builder $query, int $perPage = 12): LengthAwarePaginator
    {
        return $query->paginate($perPage)->withQueryString();
    }

    public function getBountyData(Request $request, int $perPage = 12): array
    {
        $query = $this->buildBountyQuery();
        $query = $this->applySearchFilter($query, $request);
        $query = $this->applyLanguageFilter($query, $request);

        return [
            'bounties' => $this->getPaginatedBounties($query, $perPage),
            'availableLanguages' => Bounty::getAvailableLanguages(),
            'filters' => [
                'search' => $request->get('search', ''),
                'language' => $request->get('language', ''),
            ],
        ];
    }
}

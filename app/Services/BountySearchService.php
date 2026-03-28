<?php

namespace App\Services;

use App\Models\Bounty;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BountySearchService
{
    public function buildBountyQuery(?User $user = null): Builder
    {
        return Bounty::with(['issue.repo'])
            ->active()
            ->where('status', 'open')
            ->visibleTo($user)
            ->latest();
    }

    public function applySearchFilter(Builder $query, Request $request): Builder
    {
        return $query->when($request->filled('search'), function ($q) use ($request) {
            $searchTerm = $request->get('search');

            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

            return $q->where(function ($query) use ($searchTerm, $operator) {
                $query->where('title', $operator, "%{$searchTerm}%")
                    ->orWhere('description', $operator, "%{$searchTerm}%")
                    ->orWhereHas('issue.repo', function ($repo) use ($searchTerm, $operator) {
                        $repo->where('git_id', $operator, "%{$searchTerm}%");
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

            $providers = $provider === 'bitbucket'
                ? ['bitbucket', 'jira']
                : [$provider];

            return $q->whereHas('issue', function ($issueQuery) use ($providers) {
                $issueQuery->whereIn('provider', $providers);
            });
        });
    }

    public function getPaginatedBounties(Builder $query, int $perPage = 12): LengthAwarePaginator
    {
        return $query->paginate($perPage)->withQueryString();
    }

    public function getBountyData(Request $request, int $perPage = 12): array
    {
        $user  = $request->user();
        $query = $this->buildBountyQuery($user);
        $query = $this->applySearchFilter($query, $request);
        $query = $this->applyLanguageFilter($query, $request);
        $query = $this->applyProviderFilter($query, $request);

        return [
            'bounties'           => $this->getPaginatedBounties($query, $perPage),
            'availableLanguages' => Bounty::getAvailableLanguages(),
            'availableProviders' => Issue::getAvailableProviders(),
            'filters'            => [
                'search'   => $request->get('search', ''),
                'language' => $request->get('language', ''),
                'provider' => $request->get('provider', ''),
            ],
        ];
    }
}

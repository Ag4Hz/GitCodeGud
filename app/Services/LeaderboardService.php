<?php

namespace App\Services;

use App\Models\User;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;

class LeaderboardService
{
    public bool $languageWithoutSkill = false;

    public function getLeaderboardPageData(Request $request, ?int $organizationId = null): array
    {
        $filters = $this->extractFilters($request);
        $sortDirection = $this->extractSortDirection($request);
        $skillId = $this->resolveSkillId($filters['language']);
        $leaderboardUsers = $this->getLeaderboard($sortDirection, $skillId, $filters['language'], $filters['provider'], $organizationId);

        $bountyData = $this->getBountyData($request);
        $userData = $this->getUserSearchData($filters['user_search']);

        return [
            'leaderboardUsers'   => $leaderboardUsers,
            'availableLanguages' => $bountyData['availableLanguages'] ?? [],
            'filters'            => [
                'language' => $filters['language'],
                'search'   => $filters['search'],
                'provider' => $filters['provider'],
            ],
            'sort'     => ['dir' => $sortDirection],
            'selected' => [
                'dir'      => $sortDirection,
                'skill_id' => $skillId,
            ],
            'userFilters' => ['search' => $filters['user_search']],
            'users'       => $userData['users'] ?? ['data' => []],
        ];
    }

    public function getLeaderboard(string $direction = 'desc', ?int $skillId = null, ?string $language = null, ?string $provider = null, ?int $organizationId = null): LengthAwarePaginator
    {
        $direction = $this->validateDirection($direction);

        $query = $this->buildLeaderboardQuery($skillId, $direction, $language, $provider, $organizationId);

        $paginator = $query
            ->paginate(10)
            ->withQueryString();

        return $this->addRankingsToResults($paginator, $skillId);
    }

    private function extractFilters(Request $request): array
    {
        return [
            'language'    => $request->string('language')->toString(),
            'search'      => $request->string('search')->toString(),
            'user_search' => $request->string('search_user')->toString(),
            'provider'    => $request->string('provider')->toString(),
        ];
    }

    private function extractSortDirection(Request $request): string
    {
        return $request->string('dir')->toString() ?: 'desc';
    }

    private function getBountyData(Request $request): array
    {
        $bountySearchService = new BountySearchService();
        return $bountySearchService->getBountyData($request);
    }

    private function getUserSearchData(string $searchTerm): array
    {
        $listedUsers = UserService::listUser($searchTerm);
        return UserService::searchUser($listedUsers);
    }

    private function buildLeaderboardQuery(?int $skillId, ?string $direction, ?string $language, ?string $provider, ?int $organizationId = null)
    {
        $query = User::query()->select('users.*')->with('providers');

        $provider = $provider ? strtolower(trim($provider)) : null;
        $allowedProviders = ['github', 'gitlab', 'bitbucket'];

        if ($provider && in_array($provider, $allowedProviders, true)) {
            $query->where(function ($q) use ($provider) {
                $q->whereHas('providers', function ($providerQuery) use ($provider) {
                    $providerQuery->where('provider', $provider);
                })
                    ->orWhere(function ($legacy) use ($provider) {
                        $legacy->where('oauth_provider', $provider)
                            ->whereDoesntHave('providers');
                    });
            });
        }

        if ($organizationId !== null) {
            $query->whereExists(function (\Illuminate\Database\Query\Builder $q) use ($organizationId) {
                $q->from('organization_user')
                    ->whereColumn('organization_user.user_id', 'users.id')
                    ->where('organization_user.organization_id', $organizationId);
            });
        }

        if ($skillId) {
            $query
                ->join('user_skills', 'user_skills.user_id', '=', 'users.id')
                ->where('user_skills.skill_id', $skillId)
                ->when($language && !$skillId, function (Builder $query) {
                    $query->whereNotNull('user_skills.xp');
                })
                ->addSelect('user_skills.xp as skill_xp')
                ->orderBy('user_skills.xp', $direction);
        } else {
            $query->orderBy('users.xp', $direction);
        }

        $query->orderBy('users.id');

        return $query;
    }

    private function addRankingsToResults(LengthAwarePaginator $paginator, ?int $skillId): LengthAwarePaginator
    {
        $start = ($paginator->currentPage() - 1) * $paginator->perPage();

        return $paginator->through(function ($user, $index) use ($start, $skillId) {
            $user->setAttribute('rank', $start + $index + 1);

            if ($skillId && !$user->getAttribute('skill_xp')) {
                $user->setAttribute('skill_xp', 0);
            }

            return [
                'id'       => $user->id,
                'nickname' => $user->nickname,
                'avatar'   => $user->avatar,
                'name'     => $user->name,
                'xp'       => $user->xp,
                'skill_xp' => $user->getAttribute('skill_xp') ?? 0,
                'level'    => $user->level ?? 1,
                'rank'     => $user->getAttribute('rank'),
                'providers' => $user->providers->map(fn($p) => [
                    'provider'          => $p->provider,
                    'provider_username' => $p->provider_username,
                ])->toArray(),
            ];
        });
    }

    private function resolveSkillId(?string $language): ?int
    {
        if (!$language) {
            return null;
        }

        return Skill::query()
            ->where('type', 'language')
            ->where('skill_name', $language)
            ->value('id');
    }

    private function validateDirection(string $direction): string
    {
        return strtolower($direction) === 'asc' ? 'asc' : 'desc';
    }
}

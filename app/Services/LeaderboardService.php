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
    public function getLeaderboardPageData(Request $request): array
    {
        $filters = $this->extractFilters($request);
        $sortDirection = $this->extractSortDirection($request);
        $skillId = $this->resolveSkillId($filters['language']);
        $leaderboardUsers = $this->getLeaderboard($sortDirection, $skillId, $filters['language']);

        $bountyData = $this->getBountyData($request);
        $userData = $this->getUserSearchData($filters['user_search']);

        return [
            'leaderboardUsers' => $leaderboardUsers,
            'availableLanguages' => $bountyData['availableLanguages'] ?? [],
            'filters' => [
                'language' => $filters['language'],
                'search' => $filters['search'],
            ],
            'sort' => ['dir' => $sortDirection],
            'selected' => [
                'dir' => $sortDirection,
                'skill_id' => $skillId,
            ],
            'userFilters' => ['search' => $filters['user_search']],
            'users' => $userData['users'] ?? ['data' => []],
        ];
    }

    public function getLeaderboard(string $direction = 'desc', ?int $skillId = null, ?string $language,): LengthAwarePaginator
    {
        $direction = $this->validateDirection($direction);

        $query = $this->buildLeaderboardQuery($skillId, $direction, $language);

        $paginator = $query
            ->paginate(10)
            ->withQueryString();

        return $this->addRankingsToResults($paginator, $skillId);
    }

    private function extractFilters(Request $request): array
    {
        return [
            'language' => $request->string('language')->toString(),
            'search' => $request->string('search')->toString(),
            'user_search' => $request->string('search_user')->toString(),
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

    private function buildLeaderboardQuery(?int $skillId, ?string $direction, ?string $language)
    {
        $query = User::query()->select('users.*');
        if ($skillId) {
            $query
                ->join('user_skills', 'user_skills.user_id', '=', 'users.id')
                ->where('user_skills.skill_id', $skillId)
                ->when($language && !$skillId, function (Builder $query){
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

            return $user;
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

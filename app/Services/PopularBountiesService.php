<?php

namespace App\Services;

use App\Models\Bounty;
use Illuminate\Support\Collection;

class PopularBountiesService
{
    /**
     * Get popular bounties using pure Eloquent
     */
    public function getPopularBounties(int $limit = 10): Collection
    {
        $bounties = Bounty::active()
            ->open()
            ->with(['issue.repo', 'submissions'])
            ->withCount('submissions')
            ->get();

        return $bounties->map(function ($bounty) {
            $bounty->popularity_score = $this->calculatePopularityScore($bounty);
            return $bounty;
        })
            ->sortByDesc('popularity_score')
            ->take($limit)
            ->values();
    }

    /**
     * Get trending bounties (recent with some activity)
     */
    public function getTrendingBounties(int $limit = 5, int $days = 7): Collection
    {
        $bounties = Bounty::active()
            ->open()
            ->recent($days)
            ->with(['issue.repo', 'submissions'])
            ->withCount('submissions')
            ->get();

        return $bounties->map(function ($bounty) {
            $bounty->popularity_score = $this->calculatePopularityScore($bounty);
            return $bounty;
        })
            ->filter(function ($bounty) {
                return $bounty->popularity_score > 0;
            })
            ->sortByDesc('popularity_score')
            ->take($limit)
            ->values();
    }
    /**
     * Calculate popularity score consistently
     */
    private function calculatePopularityScore(Bounty $bounty): int
    {
        $views = $bounty->views ?? 0;
        $submissionsCount = $bounty->submissions_count ?? $bounty->submissions->count();

        return $views + $submissionsCount;
    }
}

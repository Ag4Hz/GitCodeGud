<?php

namespace App\Helpers;

use App\Models\GeneralSetting;
use App\Models\LevelThreshold;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class XPHelper
{
    private static function getXPThresholds(): array
    {
        return Cache::remember('xp_thresholds', 3600, function () {
            return LevelThreshold::getThresholds();
        });
    }

    public static function calculateLevel(int $xp): int
    {
        $thresholds = self::getXPThresholds();

        for ($level = count($thresholds) - 1; $level >= 1; $level--) {
            if ($xp >= $thresholds[$level]) {
                return $level + 1;
            }
        }

        return 1;
    }

    public static function getLevelProgress(int $totalXP, int $currentLevel): array
    {
        $thresholds = self::getXPThresholds();

        $currentLevelXP = $thresholds[$currentLevel] ?? 0;
        $nextLevelXP = $thresholds[$currentLevel + 1] ?? $thresholds[count($thresholds) - 1];

        $progressXP = $totalXP - $currentLevelXP;
        $totalNeeded = $nextLevelXP - $currentLevelXP;

        $percentage = $totalNeeded > 0 ? round(($progressXP / $totalNeeded) * 100) : 100;
        $percentage = min(100, max(0, $percentage));

        return [
            'current_level_xp' => $currentLevelXP,
            'next_level_xp' => $nextLevelXP,
            'progress_xp' => $progressXP,
            'total_needed' => $totalNeeded,
            'progress_percentage' => $percentage,
        ];
    }

    public static function getUserWithXP(User $user): array
    {
        $skillsCollection = $user->skills->map(function ($skill) {
            return [
                'skill_name' => $skill->skill_name,
                'type' => $skill->type,
                'xp' => $skill->pivot->xp,
                'level' => $skill->pivot->level,
            ];
        });

        $totalXP = $skillsCollection->sum('xp');
        $skills = $skillsCollection->toArray();

        $level = self::calculateLevel($totalXP);
        $levelProgress = self::getLevelProgress($totalXP, $level);

        $userData = $user->toArray();
        $userData['avatar'] = $user->avatar;

        return array_merge($userData, [
            'total_xp' => $totalXP,
            'level' => $level,
            'skills' => $skills,
            'current_level_xp' => $levelProgress['current_level_xp'],
            'next_level_xp' => $levelProgress['next_level_xp'],
            'progress_percentage' => $levelProgress['progress_percentage'],
        ]);
    }

    public static function getXPConfigsFromDB(): array
    {
        return Cache::remember('xp_configuration', 3600, function () {
            return [
                'base_xp' => GeneralSetting::getValue('base_xp', 100),
                'bonus_multiplier' => GeneralSetting::getValue('bonus_multiplier', 1.5),
            ];
        });
    }

    public static function getLevelThresholds(): array
    {
        return Cache::remember('level_thresholds_keyed', 3600, function () {
            return LevelThreshold::getThresholds();
        });
    }

    public static function clearCaches(): void
    {
        Cache::forget('xp_thresholds');
        Cache::forget('xp_configuration');
        Cache::forget('level_thresholds_keyed');
    }

    public static function getXPStats()
    {
        return Cache::remember('admin_xp_stats', 3600, function () {
            $userXpData = DB::table('user_skills')
                ->select('user_id', DB::raw('SUM(xp) as total_xp'))
                ->groupBy('user_id')
                ->get();

            $totalUsers = User::count();
            $usersWithXp = $userXpData->count();
            $totalXpDistributed = $userXpData->sum('total_xp');
            $averageXp = $usersWithXp > 0 ? round($userXpData->avg('total_xp'), 2) : 0;
            $highestXp = $usersWithXp > 0 ? $userXpData->max('total_xp') : 0;

            $levelDistribution = self::calculateLevelDistribution($userXpData);

            return [
                'total_users' => $totalUsers,
                'users_with_xp' => $usersWithXp,
                'total_xp_distributed' => $totalXpDistributed,
                'average_xp' => $averageXp,
                'highest_xp' => $highestXp,
                'level_distribution' => $levelDistribution,
            ];
        });
    }

    public static function getXPConfigs(): array
    {
        $skillWeights = Cache::remember('admin_skill_weights', 3600, function () {
            return DB::table('skills')
                ->select('skill_name', 'multiplier')
                ->orderBy('skill_name')
                ->get()
                ->keyBy('skill_name')
                ->map(fn($skill) => $skill->multiplier)
                ->toArray();
        });

        $xpSettings = XPHelper::getXPConfigsFromDB();
        $levelThresholds = XPHelper::getLevelThresholds();

        return [
            'base_xp' => $xpSettings['base_xp'] ?? 100,
            'bonus_multiplier' => $xpSettings['bonus_multiplier'] ?? 1.5,
            'skill_weights' => $skillWeights,
            'level_thresholds' => $levelThresholds,
        ];
    }

    public static function calculateLevelDistribution($userXpData): array
    {
        $distribution = [];

        foreach ($userXpData as $userXp) {
            if ($userXp->total_xp > 0) {
                $level = XPHelper::calculateLevel($userXp->total_xp);
                $distribution[$level] = ($distribution[$level] ?? 0) + 1;
            }
        }

        if (empty($distribution)) {
            $distribution[1] = 0;
        }

        ksort($distribution);
        return $distribution;
    }
}

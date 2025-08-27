<?php

namespace App\Helpers;

use App\Models\GeneralSetting;
use App\Models\LevelThreshold;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class XPHelper
{
    private static function getXPThresholds(): array
    {
        return Cache::remember('xp_thresholds', 3600, function () {
            return LevelThreshold::orderBy('level')->pluck('xp_required')->toArray();
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

        $currentLevelXP = $thresholds[$currentLevel - 1] ?? 0;
        $nextLevelXP = $thresholds[$currentLevel] ?? $thresholds[count($thresholds) - 1];

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

    public static function getXPConfigs(): array
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
}

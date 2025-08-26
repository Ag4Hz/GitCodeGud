<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class XPHelper
{
    private static function getXPThresholds(): array
    {
        return Cache::get('xp_thresholds', [0, 1000, 5000, 15000, 30000, 60000, 120000, 250000, 400000, 500000]);
    }

    public static function calculateLevel(int $xp): int
    {
        $thresholds = self::getXPThresholds();

        for ($level = count($thresholds) - 1; $level >= 1; $level--) {
            if ($xp >= $thresholds[$level]) {
                return $level;
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
}

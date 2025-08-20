<?php

namespace App\Helpers;

class XPHelper
{
    private static array $xpThresholds = [0, 1000, 5000, 15000, 30000, 60000, 120000, 250000, 400000, 500000];

    public static function calculateLevel(int $xp): int
    {
        return match (true) {
            $xp < 1000 => 1,
            $xp < 5000 => 2,
            $xp < 15000 => 3,
            $xp < 30000 => 4,
            $xp < 60000 => 5,
            $xp < 120000 => 6,
            $xp < 250000 => 7,
            $xp < 400000 => 8,
            default => 9,
        };
    }

    public static function getLevelProgress(int $totalXP, int $currentLevel): array
    {
        $currentLevelXP = self::$xpThresholds[$currentLevel - 1] ?? 0;
        $nextLevelXP = self::$xpThresholds[$currentLevel] ?? 500000;

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

    public static function getUserWithXP($user): array
    {
        $skills = $user->skills->map(function ($skill) {
            return [
                'skill_name' => $skill->skill_name,
                'type' => $skill->type ?? 'other',
                'xp' => $skill->pivot->xp,
                'level' => $skill->pivot->level,
            ];
        })->toArray();

        $totalXP = collect($skills)->sum('xp');

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

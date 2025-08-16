<?php

namespace App\Helpers;

class XPHelper
{
    public static function getUserXPData(): array
    {
        return [
            'total_xp' => 9000,
            'skills' => [
                [
                    'skill_name' => 'PHP',
                    'xp' => 800,
                    'level' => 2,
                ],
                [
                    'skill_name' => 'JavaScript',
                    'xp' => 1200,
                    'level' => 2,
                ],
            ],
        ];
    }

    public static function calculateLevel(int $xp): int
    {
        if ($xp < 1000) return 1;
        if ($xp < 5000) return 2;
        if ($xp < 15000) return 3;
        if ($xp < 30000) return 4;
        if ($xp < 60000) return 5;
        if ($xp < 120000) return 6;
        if ($xp < 250000) return 7;
        if ($xp < 400000) return 8;
        return 9;
    }
    public static function getUserWithXP($user): array
    {
        $xpData = self::getUserXPData();
        $totalXP = $xpData['total_xp'];

        return array_merge($user->toArray(), [
            'total_xp' => $totalXP,
            'level' => self::calculateLevel($totalXP),
            'skills' => $xpData['skills'],
        ]);
    }
}

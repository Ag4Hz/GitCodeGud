<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's public profile page with XP and level information.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

        // Dummy XP data for testing - set this to any value you want
        $totalXP = 5000; // Change this number to test different XP values

        // Calculate level from total XP
        $level = $this->calculateLevel($totalXP);

        // Dummy skills data for testing
        $skills = [
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
        ];

        return Inertia::render('Profile', [
            'user' => array_merge($user->toArray(), [
                'total_xp' => $totalXP,
                'level' => $level,
                'skills' => $skills,
            ]),
        ]);
    }

    /**
     * Calculate level from total XP (matches SkillUserFactory logic)
     */
    private function calculateLevel(int $xp): int
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
}

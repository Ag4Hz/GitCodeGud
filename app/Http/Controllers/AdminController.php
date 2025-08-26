<?php

namespace App\Http\Controllers;

use App\Helpers\XPHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        $xpStats = $this->getXPStats();
        $xpConfig = $this->getXPConfigs();

        return Inertia::render('Admin', [
            'xpStats' => $xpStats,
            'xpConfig' => $xpConfig,
        ]);
    }

    private function getXPStats()
    {
        // Single query to get all user XP data
        $userXpData = DB::table('user_skills')
            ->select('user_id', DB::raw('SUM(xp) as total_xp'))
            ->groupBy('user_id')
            ->get();

        $totalUsers = User::count();
        $usersWithXp = $userXpData->count();
        $totalXpDistributed = $userXpData->sum('total_xp');
        $averageXp = $usersWithXp > 0 ? round($userXpData->avg('total_xp'), 2) : 0;
        $highestXp = $usersWithXp > 0 ? $userXpData->max('total_xp') : 0;

        // Calculate level distribution
        $levelDistribution = $this->calculateLevelDistribution($userXpData);

        return [
            'total_users' => $totalUsers,
            'users_with_xp' => $usersWithXp,
            'total_xp_distributed' => $totalXpDistributed,
            'average_xp' => $averageXp,
            'highest_xp' => $highestXp,
            'level_distribution' => $levelDistribution,
        ];
    }

    private function getXPConfigs()
    {
        // Get all skills with their weights in a single query
        $skillWeights = DB::table('skills')
            ->select('skill_name', 'multiplier')
            ->orderBy('skill_name')
            ->get()
            ->keyBy('skill_name')
            ->map(fn($skill) => $skill->multiplier);

        // Get XP configuration from XPHelper
        $xpSettings = XPHelper::getXPConfigs();
        $levelThresholds = XPHelper::getLevelThresholds();

        return [
            'base_xp' => $xpSettings['base_xp'] ?? 100,
            'bonus_multiplier' => $xpSettings['bonus_multiplier'] ?? 1.5,
            'skill_weights' => $skillWeights->toArray(),
            'level_thresholds' => $levelThresholds,
        ];
    }

    private function calculateLevelDistribution($userXpData)
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

    public function updateThresholds(Request $request)
    {
        $thresholds = $request->validate([
            'thresholds' => 'required|array',
            'thresholds.*' => 'required|integer|min:0'
        ]);

        // Sort thresholds by value to maintain proper level order
        $sortedThresholds = array_values($thresholds['thresholds']);
        sort($sortedThresholds);

        // Cache the new thresholds
        Cache::put('xp_thresholds', $sortedThresholds, now()->addYear());

        return back()->with('success', 'Level thresholds updated successfully!');
    }

    public function updateSkillWeights(Request $request)
    {
        $skillWeights = $request->validate([
            'skillWeights' => 'required|array',
            'skillWeights.*.skill_name' => 'required|string|max:255',
            'skillWeights.*.multiplier' => 'required|numeric|min:0|max:10'
        ]);

        foreach ($skillWeights['skillWeights'] as $skillData) {
            DB::table('skills')
                ->where('skill_name', $skillData['skill_name'])
                ->update(['multiplier' => $skillData['multiplier']]);
        }

        return back()->with('success', 'Skill weights updated successfully!');
    }
}

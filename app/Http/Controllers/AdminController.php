<?php

namespace App\Http\Controllers;

use App\Helpers\XPHelper;
use App\Models\User;
use App\Models\LevelThreshold;
use App\Models\GeneralSetting;
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

            $levelDistribution = $this->calculateLevelDistribution($userXpData);

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

    private function getXPConfigs()
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

        $xpSettings = XPHelper::getXPConfigs();
        $levelThresholds = XPHelper::getLevelThresholds();

        return [
            'base_xp' => $xpSettings['base_xp'] ?? 100,
            'bonus_multiplier' => $xpSettings['bonus_multiplier'] ?? 1.5,
            'skill_weights' => $skillWeights,
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

    public function updateAllSettings(Request $request)
    {
        $validated = $request->validate([
            'xp_settings.base_xp' => 'sometimes|integer|min:1|max:10000',
            'xp_settings.bonus_multiplier' => 'sometimes|numeric|min:0.1|max:10',
            'thresholds' => 'sometimes|array',
            'thresholds.*' => 'integer|min:0',
            'skill_weights' => 'sometimes|array',
            'skill_weights.*.skill_name' => 'string|max:255',
            'skill_weights.*.multiplier' => 'numeric|min:0|max:10'
        ]);

        DB::transaction(function () use ($validated) {
            // Update XP settings if provided
            if (isset($validated['xp_settings'])) {
                foreach ($validated['xp_settings'] as $key => $value) {
                    $type = $key === 'base_xp' ? 'integer' : 'float';
                    GeneralSetting::setValue($key, $value, $type);
                }
            }

            // Update thresholds if provided
            if (isset($validated['thresholds'])) {
                $sortedThresholds = array_values($validated['thresholds']);
                sort($sortedThresholds);
                LevelThreshold::updateThresholds($sortedThresholds);
            }

            // Update skill weights if provided
            if (isset($validated['skill_weights'])) {
                foreach ($validated['skill_weights'] as $skillData) {
                    DB::table('skills')
                        ->where('skill_name', $skillData['skill_name'])
                        ->update(['multiplier' => $skillData['multiplier']]);
                }
            }
        });

        // Clear all related caches
        $this->clearAllCaches();

        return back()->with('success', 'Settings updated successfully!');
    }

    public function updateThresholds(Request $request)
    {
        $thresholds = $request->validate([
            'thresholds' => 'required|array',
            'thresholds.*' => 'required|integer|min:0'
        ]);

        $sortedThresholds = array_values($thresholds['thresholds']);
        sort($sortedThresholds);

        LevelThreshold::updateThresholds($sortedThresholds);
        XPHelper::clearCaches();

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

    public function updateXPSettings(Request $request)
    {
        $xpSettings = $request->validate([
            'base_xp' => 'required|integer|min:1|max:10000',
            'bonus_multiplier' => 'required|numeric|min:0.1|max:10'
        ]);

        GeneralSetting::setValue('base_xp', $xpSettings['base_xp'], 'integer');
        GeneralSetting::setValue('bonus_multiplier', $xpSettings['bonus_multiplier'], 'float');

        XPHelper::clearCaches();

        return back()->with('success', 'XP settings updated successfully!');
    }

    private function clearAllCaches(): void
    {
        XPHelper::clearCaches();
        Cache::forget('admin_xp_stats');
        Cache::forget('admin_skill_weights');
    }
}

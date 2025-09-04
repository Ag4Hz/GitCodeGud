<?php

namespace App\Http\Controllers;

use App\Helpers\XPHelper;
use App\Http\Requests\UpdateAllSettingsRequest;
use App\Http\Requests\UpdateSkillWeightsRequest;
use App\Http\Requests\UpdateThresholdsRequest;
use App\Http\Requests\UpdateXPSettingsRequest;
use App\Models\User;
use App\Models\LevelThreshold;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        $xpStats = XPHelper::getXPStats();
        $xpConfig = XPHelper::getXPConfigs();

        return Inertia::render('Admin', [
            'xpStats' => $xpStats,
            'xpConfig' => $xpConfig,
        ]);
    }

    public function updateAllSettings(UpdateAllSettingsRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            // Update XP settings if provided
            if (isset($validated['xp_settings'])) {
                foreach ($validated['xp_settings'] as $key => $value) {
                    $type = $key === 'base_xp' ? 'integer' : 'float';
                    GeneralSetting::setValue($key, $value, $type);
                }
                DB::table('general_settings')->update(['updated_at' => now()]);
            }

            // Update thresholds if provided
            if (isset($validated['thresholds'])) {
                $sortedThresholds = array_values($validated['thresholds']);
                sort($sortedThresholds);
                LevelThreshold::updateThresholds($sortedThresholds);
                DB::table('level_thresholds')->update(['updated_at' => now()]);
            }

            // Update skill weights if provided
            if (isset($validated['skill_weights'])) {
                $skillNames = array_column($validated['skill_weights'], 'skill_name');
                $multipliers = array_column($validated['skill_weights'], 'multiplier');

                if (!empty($skillNames)) {
                    $cases = [];
                    $bindings = [];

                    foreach ($validated['skill_weights'] as $skillData) {
                        $cases[] = "WHEN skill_name = ? THEN ?::numeric";
                        $bindings[] = $skillData['skill_name'];
                        $bindings[] = $skillData['multiplier'];
                    }

                    $bindings = array_merge($bindings, $skillNames);

                    $caseStatement = implode(' ', $cases);
                    $placeholders = implode(',', array_fill(0, count($skillNames), '?'));

                    DB::update("
                        UPDATE skills
                        SET multiplier = CASE {$caseStatement} END
                        WHERE skill_name IN ({$placeholders})
                    ", $bindings);
                }
                DB::table('skills')->update(['updated_at' => now()]);
            }
        });

        // Clear all related caches
        $this->clearAllCaches();

        return back()->with('success', 'Settings updated successfully!');
    }

    public function updateThresholds(UpdateThresholdsRequest $request)
    {
        $thresholds = $request->validated();

        $sortedThresholds = array_values($thresholds['thresholds']);
        sort($sortedThresholds);

        LevelThreshold::updateThresholds($sortedThresholds);
        DB::table('level_thresholds')->update(['updated_at' => now()]);
        XPHelper::clearCaches();

        return back()->with('success', 'Level thresholds updated successfully!');
    }

    public function updateSkillWeights(UpdateSkillWeightsRequest $request)
    {
        $skillWeights = $request->validated();

        if (!empty($skillWeights['skillWeights'])) {
            $cases = [];
            $bindings = [];
            $skillNames = [];

            foreach ($skillWeights['skillWeights'] as $skillData) {
                $cases[] = "WHEN skill_name = ? THEN ?::numeric";
                $bindings[] = $skillData['skill_name'];
                $bindings[] = $skillData['multiplier'];
                $skillNames[] = $skillData['skill_name'];
            }

            $bindings = array_merge($bindings, $skillNames);

            $caseStatement = implode(' ', $cases);
            $placeholders = implode(',', array_fill(0, count($skillNames), '?'));

            DB::update("
                UPDATE skills
                SET multiplier = CASE {$caseStatement} END
                WHERE skill_name IN ({$placeholders})
            ", $bindings);
        }

        $this->clearAllCaches();
        DB::table('skills')->update(['updated_at' => now()]);

        return back()->with('success', 'Skill weights updated successfully!');
    }

    public function updateXPSettings(UpdateXPSettingsRequest $request)
    {
        $xpSettings = $request->validated();

        GeneralSetting::setValue('base_xp', $xpSettings['base_xp'], 'integer');
        GeneralSetting::setValue('bonus_multiplier', $xpSettings['bonus_multiplier'], 'float');

        XPHelper::clearCaches();
        DB::table('general_settings')->update(['updated_at' => now()]);

        return back()->with('success', 'XP settings updated successfully!');
    }

    private function clearAllCaches(): void
    {
        XPHelper::clearCaches();
        Cache::forget('admin_xp_stats');
        Cache::forget('admin_skill_weights');
    }

    public function recalculateXp() {
        try {
            DB::statement('CALL recalculate_user_xp()');
            $this->clearAllCaches();

            return back()->with('success', 'XP settings updated successfully!');
        } catch (\Exception $e) {
            \Log::error('XP recalculation failed: ' . $e->getMessage());

            return back()->withErrors('An error occurred while recalculating XP. Please try again later.');
        }
    }
}

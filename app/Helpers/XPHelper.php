<?php

namespace App\Helpers;

use App\Models\GeneralSetting;
use App\Models\LevelThreshold;
use App\Models\User;
use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\Submission;
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

        foreach (array_reverse($thresholds, true) as $level => $requiredXP) {
            if ($xp >= $requiredXP) {
                return $level;
            }
        }

        return 1;
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

    public static function getLevelProgress(int $totalXP, int $currentLevel): array
    {
        $thresholds = self::getXPThresholds();

        $currentLevelXP = $thresholds[$currentLevel] ?? 0;
        $nextLevel = $currentLevel + 1;
        $nextLevelXP = $thresholds[$nextLevel] ?? null;

        if ($nextLevelXP === null) {
            return [
                'current_level_xp' => $currentLevelXP,
                'next_level_xp' => $currentLevelXP,
                'progress_xp' => $totalXP - $currentLevelXP,
                'total_needed' => 0,
                'progress_percentage' => 100,
            ];
        }

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

    public static function awardSubmissionXP(Submission $submission): void
    {

        if ($submission->status !== 'accepted') {
            return;
        }

        $user = $submission->user;
        $bounty = $submission->bounty;
        $xpToAward = $bounty->reward_xp;
        $languages = $bounty->languages ?? [];

        DB::transaction(function () use ($user, $xpToAward, $languages) {
            self::distributeXPToSkills($user, $xpToAward, $languages);
            self::updateUserTotalXP($user);
        });
    }

    public static function canAffordBounty(User $user, int $xp): bool
    {
        $user->load('skills');
        $totalXP = $user->skills->sum('pivot.xp');
        return $totalXP >= $xp;
    }

    public static function deductBountyXP(User $user, int $xp): void
    {
        DB::transaction(function () use ($user, $xp) {
            self::deductXPFromSkills($user, $xp);
            self::updateUserTotalXP($user);
        });
    }

    private static function deductXPFromSkills(User $user, int $totalXP): void
    {
        $user->load('skills');
        $remaining = $totalXP;

        foreach ($user->skills()->orderByPivot('xp', 'desc')->get() as $skill) {
            if ($remaining <= 0) break;

            $available = $skill->pivot->xp;
            $deduct = min($available, $remaining);

            $newXP = $available - $deduct;
            SkillUser::where('user_id', $user->id)
                ->where('skill_id', $skill->id)
                ->update([
                    'xp'    => $newXP,
                    'level' => self::calculateLevel($newXP),
                ]);

            $remaining -= $deduct;
        }
    }

    private static function distributeXPToSkills(User $user, int $totalXP, array $languages): void
    {
        if (empty($languages)) {
            self::addXPToSkill($user, 'General', $totalXP);
            return;
        }

        $xpPerLanguage = intval($totalXP / count($languages));
        $remainder = $totalXP % count($languages);

        foreach ($languages as $index => $language) {
            $xp = $xpPerLanguage + ($index === 0 ? $remainder : 0);
            self::addXPToSkill($user, $language, $xp);
        }
    }

    private static function addXPToSkill(User $user, string $skillName, int $xp): void
    {
        $skill = Skill::firstOrCreate(
            ['skill_name' => $skillName],
            ['type' => 'language', 'multiplier' => 1.0]
        );

        $userSkill = SkillUser::firstOrCreate(
            ['user_id' => $user->id, 'skill_id' => $skill->id],
            ['xp' => 0, 'level' => 1]
        );

        $newXP = $userSkill->xp + (int) round($xp * $skill->multiplier);
        $userSkill->update([
            'xp' => $newXP,
            'level' => self::calculateLevel($newXP)
        ]);
    }

    private static function updateUserTotalXP(User $user): void
    {
        $user->load('skills');
        $userWithXP = self::getUserWithXP($user);
        $user->update(['xp' => $userWithXP['total_xp']]);
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
        $skills = Cache::remember('admin_skills', 3600, function () {
            return DB::table('skills')
                ->select('skill_name', 'multiplier', 'type')
                ->orderBy('skill_name')
                ->get()
                ->map(fn($skill) => [
                    'skill_name' => $skill->skill_name,
                    'multiplier' => (float) $skill->multiplier,
                    'type' => $skill->type,
                ])
                ->toArray();
        });

        $skillWeights = Cache::remember('admin_skill_weights', 3600, function () {
            return DB::table('skills')
                ->select('skill_name', 'multiplier')
                ->orderBy('skill_name')
                ->get()
                ->keyBy('skill_name')
                ->map(fn($skill) => (float) $skill->multiplier)
                ->toArray();
        });

        $xpSettings = XPHelper::getXPConfigsFromDB();
        $levelThresholds = XPHelper::getLevelThresholds();

        return [
            'base_xp' => $xpSettings['base_xp'] ?? 100,
            'bonus_multiplier' => $xpSettings['bonus_multiplier'] ?? 1.5,
            'skills' => $skills,
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
    public static function getLevelThresholds(): array
    {
        return Cache::remember('level_thresholds_keyed', 3600, function () {
            return LevelThreshold::getThresholds();
        });
    }
    public static function grantStarterXP(User $user): void
    {
        DB::transaction(function () use ($user) {
            self::addXPToSkill($user, 'General', 200);
            self::updateUserTotalXP($user);
        });
    }

}

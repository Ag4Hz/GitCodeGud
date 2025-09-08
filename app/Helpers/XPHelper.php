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

        $newXP = $userSkill->xp + $xp;
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
}

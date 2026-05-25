<?php

namespace App\Services;

use App\Models\Bounty;
use App\Models\User;
use App\Helpers\XPHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BountyRecommendationService
{
    public function getRecommendations(User $user): array
    {
        $cacheKey = "recommendations_user_{$user->id}";

        return Cache::remember($cacheKey, 3600, function () use ($user) {
            return $this->fetchFromOpenAI($user);
        });
    }

    public function clearCache(User $user): void
    {
        Cache::forget("recommendations_user_{$user->id}");
    }

    private function fetchFromOpenAI(User $user): array
    {
        $user->load('skills', 'submissions.bounty');

        $skills = $user->skills
            ->sortByDesc('pivot.xp')
            ->take(8)
            ->map(function ($skill) {
                $xp = $skill->pivot->xp;
                $level = $skill->pivot->level;
                $thresholds = XPHelper::getLevelThresholds();
                $nextLevelXP = $thresholds[$level + 1] ?? null;
                $currentLevelXP = $thresholds[$level] ?? 0;
                $progress = $nextLevelXP
                    ? round((($xp - $currentLevelXP) / ($nextLevelXP - $currentLevelXP)) * 100)
                    : 100;
                $xpNeeded = $nextLevelXP ? max(0, $nextLevelXP - $xp) : 0;

                return [
                    'skill'      => $skill->skill_name,
                    'xp'         => $xp,
                    'level'      => $level,
                    'progress'   => $progress . '%',
                    'xp_needed'  => $xpNeeded,
                ];
            })->values()->toArray();

        $acceptedSubmissions = $user->submissions()
            ->where('status', 'accepted')
            ->with('bounty')
            ->latest()
            ->take(7)
            ->get();

        $solvedBountyIds = $acceptedSubmissions->pluck('bounty_id')->toArray();

        $solvedBounties = $acceptedSubmissions->map(fn($s) => [
            'title'     => $s->bounty->title,
            'languages' => $s->bounty->languages ?? [],
        ])->toArray();

        $skillsCollection = collect($skills);

        $openBounties = Bounty::with('issue.repo')
            ->where('status', 'open')
            ->whereNull('organization_id')
            ->whereNotIn('id', $solvedBountyIds)
            ->whereHas('issue', fn($q) => $q->whereHas('repo', fn($q2) => $q2->where('user_id', '!=', $user->id)))
            ->latest()
            ->take(25)
            ->get()
            ->map(function ($b) use ($skillsCollection) {
                $matchingSkill = $skillsCollection->first(fn($s) =>
                collect($b->languages ?? [])->contains($s['skill'])
                );

                return [
                    'id'             => $b->id,
                    'title'          => $b->title,
                    'languages'      => $b->languages ?? [],
                    'reward_xp'      => $b->reward_xp,
                    'provider'       => $b->issue->provider ?? '',
                    'matching_skill' => $matchingSkill ? $matchingSkill['skill'] : null,
                    'skill_level'    => $matchingSkill ? $matchingSkill['level'] : null,
                    'skill_progress' => $matchingSkill ? $matchingSkill['progress'] : null,
                    'xp_needed'      => $matchingSkill ? $matchingSkill['xp_needed'] : null,
                ];
            })->toArray();

        if (empty($openBounties)) {
            return [];
        }

        $prompt = $this->buildPrompt($skills, $solvedBounties, $openBounties);

        $response = Http::withToken(config('services.openai.key'))
            ->timeout(25)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'      => 'gpt-4o-mini',
                'max_tokens' => 500,
                'messages'   => [
                    [
                        'role'    => 'system',
                        'content' => 'You are a developer bounty recommendation engine. Always respond with valid JSON only, no markdown, no explanation outside the JSON.',
                    ],
                    [
                        'role'    => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

        if (!$response->successful()) {
            return [];
        }

        $content = $response->json('choices.0.message.content', '');

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            return $data['recommendations'] ?? [];
        } catch (\JsonException) {
            return [];
        }
    }

    private function buildPrompt(array $skills, array $solved, array $open): string
    {
        $skillsJson = json_encode($skills, JSON_PRETTY_PRINT);
        $solvedJson = json_encode($solved, JSON_PRETTY_PRINT);
        $openJson   = json_encode($open, JSON_PRETTY_PRINT);

        return <<<PROMPT
You are recommending open source bounties to a developer.

DEVELOPER SKILLS (top 8, with level and progress):
{$skillsJson}

RECENTLY SOLVED BOUNTIES (for pattern matching only):
{$solvedJson}

AVAILABLE OPEN BOUNTIES (with pre-matched skill data — use matching_skill, skill_level, skill_progress, xp_needed directly):
{$openJson}

Select 7 bounties. Follow these rules strictly:
1. NEVER recommend more than 2 bounties with the same matching_skill
2. Prioritize variety — spread recommendations across different skills
3. Prioritize bounties where skill_progress is high (close to leveling up)
4. Include some bounties similar to recently solved ones (similar_work category)

Write a unique reason for each. Rules for reasons:
- Under 17 words
- Use "you/your" directly, never "the developer"
- Be specific but vary what you mention: sometimes use xp_needed, sometimes progress %, sometimes similarity to past work, sometimes just the skill match. Don't always mention XP numbers.
- Never repeat the same sentence structure twice
- Do not use generic phrases like "significant rewards", "rewarding results", "fostering growth", "leveling opportunity"

Respond with ONLY this JSON:
{
  "recommendations": [
    {
      "id": <bounty_id>,
      "reason": "<unique reason>",
      "category": "<skill_match | level_up | similar_work>"
    }
  ]
}
PROMPT;
    }
}

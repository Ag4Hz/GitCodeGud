<?php

declare(strict_types=1);

use App\Models\Skill;
use App\Models\SkillUser;
use App\Models\User;
use App\Models\UserProvider;
use App\Services\LeaderboardService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
uses(DatabaseTruncation::class);


beforeEach(function () {
    $this->service = new LeaderboardService();

    $this->phpSkill = Skill::factory()->create(['skill_name' => 'PHP', 'type' => 'language', 'multiplier' => 1.0]);
    $this->jsSkill = Skill::factory()->create(['skill_name' => 'JavaScript', 'type' => 'language', 'multiplier' => 1.0]);
    $this->pythonSkill = Skill::factory()->create(['skill_name' => 'Python', 'type' => 'language', 'multiplier' => 1.0]);

    $this->topUser = User::factory()->create([
        'nickname' => 'CodeMaster',
        'name' => 'Code Master',
        'xp' => 5000,
    ]);
    UserProvider::create([
        'user_id' => $this->topUser->id,
        'provider' => 'github',
        'provider_id' => fake()->numerify('########'),
        'provider_username' => 'codemaster_gh',
        'provider_email' => fake()->email(),
        'nickname' => 'codemaster_gh',
        'avatar' => fake()->imageUrl(),
        'token' => fake()->sha256(),
        'refresh_token' => fake()->sha256(),
    ]);
    SkillUser::factory()->create([
        'user_id' => $this->topUser->id,
        'skill_id' => $this->phpSkill->id,
        'xp' => 3000,
    ]);

    $this->middleUser = User::factory()->create([
        'nickname' => 'DevPro',
        'name' => 'Dev Pro',
        'xp' => 3000,
    ]);
    UserProvider::create([
        'user_id' => $this->middleUser->id,
        'provider' => 'gitlab',
        'provider_id' => fake()->numerify('########'),
        'provider_username' => 'devpro_gl',
        'provider_email' => fake()->email(),
        'nickname' => 'devpro_gl',
        'avatar' => fake()->imageUrl(),
        'token' => fake()->sha256(),
        'refresh_token' => fake()->sha256(),
    ]);
    SkillUser::factory()->create([
        'user_id' => $this->middleUser->id,
        'skill_id' => $this->phpSkill->id,
        'xp' => 2000,
    ]);
    SkillUser::factory()->create([
        'user_id' => $this->middleUser->id,
        'skill_id' => $this->jsSkill->id,
        'xp' => 1000,
    ]);

    $this->beginnerUser = User::factory()->create([
        'nickname' => 'NewbieCoder',
        'name' => 'Newbie Coder',
        'xp' => 1000,
    ]);
    UserProvider::create([
        'user_id' => $this->beginnerUser->id,
        'provider' => 'bitbucket',
        'provider_id' => fake()->numerify('########'),
        'provider_username' => 'newbie_bb',
        'provider_email' => fake()->email(),
        'nickname' => 'newbie_bb',
        'avatar' => fake()->imageUrl(),
        'token' => fake()->sha256(),
        'refresh_token' => fake()->sha256(),
    ]);
    SkillUser::factory()->create([
        'user_id' => $this->beginnerUser->id,
        'skill_id' => $this->jsSkill->id,
        'xp' => 1000,
    ]);

    $this->multiProviderUser = User::factory()->create([
        'nickname' => 'MultiDev',
        'name' => 'Multi Provider Dev',
        'xp' => 2000,
    ]);
    UserProvider::create([
        'user_id' => $this->multiProviderUser->id,
        'provider' => 'github',
        'provider_id' => fake()->numerify('########'),
        'provider_username' => 'multidev_gh',
        'provider_email' => fake()->email(),
        'nickname' => 'multidev_gh',
        'avatar' => fake()->imageUrl(),
        'token' => fake()->sha256(),
        'refresh_token' => fake()->sha256(),
    ]);
    UserProvider::create([
        'user_id' => $this->multiProviderUser->id,
        'provider' => 'gitlab',
        'provider_id' => fake()->numerify('########'),
        'provider_username' => 'multidev_gl',
        'provider_email' => fake()->email(),
        'nickname' => 'multidev_gl',
        'avatar' => fake()->imageUrl(),
        'token' => fake()->sha256(),
        'refresh_token' => fake()->sha256(),
    ]);
    SkillUser::factory()->create([
        'user_id' => $this->multiProviderUser->id,
        'skill_id' => $this->pythonSkill->id,
        'xp' => 2000,
    ]);
});

describe('Leaderboard Total XP Sorting', function () {
    it('sorts users by XP in descending order by default', function () {
        $results = $this->service->getLeaderboard('desc', null, null);

        expect($results->items()[0]['xp'])->toBe(5000)
            ->and($results->items()[1]['xp'])->toBe(3000)
            ->and($results->items()[2]['xp'])->toBe(2000)
            ->and($results->items()[3]['xp'])->toBe(1000);
    });

    it('can sort users by XP in ascending order', function () {
        $results = $this->service->getLeaderboard('asc', null, null);

        expect($results->items()[0]['xp'])->toBe(1000)
            ->and($results->items()[1]['xp'])->toBe(2000)
            ->and($results->items()[2]['xp'])->toBe(3000)
            ->and($results->items()[3]['xp'])->toBe(5000);
    });
});

describe('Leaderboard Skill Filtering', function () {
    it('can filter leaderboard by skill/language', function () {
        $results = $this->service->getLeaderboard('desc', $this->phpSkill->id, 'PHP');

        expect($results->items())->toHaveCount(2)
            ->and($results->items()[0]['nickname'])->toBe('CodeMaster')
            ->and($results->items()[1]['nickname'])->toBe('DevPro');
    });

    it('sorts by skill XP when skill filter is applied', function () {
        $results = $this->service->getLeaderboard('desc', $this->phpSkill->id, 'PHP');

        expect($results->items()[0]['skill_xp'])->toBe(3000)
            ->and($results->items()[1]['skill_xp'])->toBe(2000);
    });

    it('can filter by different skills', function () {
        $jsResults = $this->service->getLeaderboard('desc', $this->jsSkill->id, 'JavaScript');
        $pythonResults = $this->service->getLeaderboard('desc', $this->pythonSkill->id, 'Python');

        expect($jsResults->items())->toHaveCount(2)
            ->and($pythonResults->items())->toHaveCount(1);
    });

    it('returns empty results when no users have the filtered skill', function () {
        $unusedSkill = Skill::factory()->create(['skill_name' => 'Rust', 'type' => 'language']);
        $results = $this->service->getLeaderboard('desc', $unusedSkill->id, 'Rust');

        expect($results->items())->toHaveCount(0);
    });
});

describe('Leaderboard Ranking Numbers', function () {
    it('assigns correct ranking numbers starting from 1', function () {
        $results = $this->service->getLeaderboard('desc', null, null);

        expect($results->items()[0]['rank'])->toBe(1)
            ->and($results->items()[1]['rank'])->toBe(2)
            ->and($results->items()[2]['rank'])->toBe(3)
            ->and($results->items()[3]['rank'])->toBe(4);
    });

    it('adjusts ranking numbers for paginated results', function () {
        User::factory()->count(11)->create();

        $resultsPage2 = $this->service->getLeaderboard('desc', null, null);

        expect($resultsPage2->items()[0]['rank'])->toBeGreaterThanOrEqual(1);
    });

    it('maintains ranking when sorting direction changes', function () {
        $descResults = $this->service->getLeaderboard('desc', null, null);
        $ascResults = $this->service->getLeaderboard('asc', null, null);

        expect($descResults->items()[0]['rank'])->toBe(1)
            ->and($ascResults->items()[0]['rank'])->toBe(1);
    });
});

describe('Leaderboard Result Structure', function () {
    it('includes all required user fields', function () {
        $results = $this->service->getLeaderboard('desc', null, null);
        $user = $results->items()[0];

        expect($user)->toHaveKeys(['id', 'nickname', 'name', 'xp', 'avatar', 'rank', 'skill_xp', 'providers']);
    });

    it('includes provider information for each user', function () {
        $results = $this->service->getLeaderboard('desc', null, null);
        $user = $results->items()[0];

        expect($user['providers'])->toHaveCount(1);
    });

    it('includes correct provider badges', function () {
        $results = $this->service->getLeaderboard('desc', null, null);

        $githubUser = collect($results->items())->firstWhere('nickname', 'CodeMaster');
        $gitlabUser = collect($results->items())->firstWhere('nickname', 'DevPro');
        $bitbucketUser = collect($results->items())->firstWhere('nickname', 'NewbieCoder');

        expect($githubUser['providers'][0]['provider'])->toBe('github')
            ->and($gitlabUser['providers'][0]['provider'])->toBe('gitlab')
            ->and($bitbucketUser['providers'][0]['provider'])->toBe('bitbucket');
    });

    it('sets skill_xp to 0 when no skill filter applied', function () {
        $results = $this->service->getLeaderboard('desc', null, null);

        expect($results->items()[0]['skill_xp'])->toBe(0);
    });

    it('includes skill_xp when skill filter applied', function () {
        $results = $this->service->getLeaderboard('desc', $this->phpSkill->id, 'PHP');

        expect($results->items()[0]['skill_xp'])->toBeGreaterThan(0);
    });
});

describe('Leaderboard Pagination', function () {
    it('paginates results with 10 items per page', function () {
        User::factory()->count(15)->create();

        $results = $this->service->getLeaderboard('desc', null, null);

        expect($results->perPage())->toBe(10)
            ->and($results->total())->toBeGreaterThanOrEqual(15);
    });

    it('preserves query parameters in pagination', function () {
        for ($i = 0; $i < 15; $i++) {
            $user = User::factory()->create();
            SkillUser::factory()->create([
                'user_id' => $user->id,
                'skill_id' => $this->phpSkill->id,
                'xp' => fake()->numberBetween(100, 5000),
            ]);
        }

        $results = $this->service->getLeaderboard('asc', $this->phpSkill->id, 'PHP');

        expect($results->hasPages())->toBeTrue();
    });
});

describe('Leaderboard with Multiple Providers', function () {
    it('displays users with multiple provider connections', function () {
        $results = $this->service->getLeaderboard('desc', null, null);
        $multiProviderUser = collect($results->items())->firstWhere('nickname', 'MultiDev');

        expect($multiProviderUser['providers'])->toHaveCount(2);

        $providers = collect($multiProviderUser['providers'])->pluck('provider')->toArray();
        expect($providers)->toContain('github')
            ->and($providers)->toContain('gitlab');
    });
});

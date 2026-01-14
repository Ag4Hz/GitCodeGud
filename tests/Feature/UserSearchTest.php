<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserProvider;
use App\Services\UserService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
uses(DatabaseTruncation::class);


beforeEach(function () {
    $this->userWithGitHub = User::factory()->create([
        'nickname' => 'TestBuddy',
        'name' => 'Test Buddy User',
    ]);
    UserProvider::create([
        'user_id' => $this->userWithGitHub->id,
        'provider' => 'github',
        'provider_id' => fake()->numerify('########'),
        'provider_username' => 'testbuddy_gh',
        'provider_email' => fake()->email(),
        'nickname' => 'testbuddy_gh',
        'avatar' => fake()->imageUrl(),
        'token' => fake()->sha256(),
        'refresh_token' => fake()->sha256(),
    ]);

    $this->userWithGitLab = User::factory()->create([
        'nickname' => 'CodeMaster',
        'name' => 'Code Master Dev',
    ]);
    UserProvider::create([
        'user_id' => $this->userWithGitLab->id,
        'provider' => 'gitlab',
        'provider_id' => fake()->numerify('########'),
        'provider_username' => 'codemaster_gl',
        'provider_email' => fake()->email(),
        'nickname' => 'codemaster_gl',
        'avatar' => fake()->imageUrl(),
        'token' => fake()->sha256(),
        'refresh_token' => fake()->sha256(),
    ]);

    $this->userWithBitbucket = User::factory()->create([
        'nickname' => 'BugHunter',
        'name' => 'Bug Hunter Pro',
    ]);
    UserProvider::create([
        'user_id' => $this->userWithBitbucket->id,
        'provider' => 'bitbucket',
        'provider_id' => fake()->numerify('########'),
        'provider_username' => 'bughunter_bb',
        'provider_email' => fake()->email(),
        'nickname' => 'bughunter_bb',
        'avatar' => fake()->imageUrl(),
        'token' => fake()->sha256(),
        'refresh_token' => fake()->sha256(),
    ]);

    $this->userWithMultipleProviders = User::factory()->create([
        'nickname' => 'MultiDev',
        'name' => 'Multi Provider Developer',
    ]);
    UserProvider::create([
        'user_id' => $this->userWithMultipleProviders->id,
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
        'user_id' => $this->userWithMultipleProviders->id,
        'provider' => 'gitlab',
        'provider_id' => fake()->numerify('########'),
        'provider_username' => 'multidev_gl',
        'provider_email' => fake()->email(),
        'nickname' => 'multidev_gl',
        'avatar' => fake()->imageUrl(),
        'token' => fake()->sha256(),
        'refresh_token' => fake()->sha256(),
    ]);
});

describe('User Search by Nickname', function () {
    it('can search for users by nickname', function () {
        $results = UserService::listUser('TestBuddy');

        expect($results->items())->toHaveCount(1)
            ->and($results->items()[0]['nickname'])->toBe('TestBuddy');
    });

    it('returns empty results when no match found', function () {
        $results = UserService::listUser('NonExistentUser');

        expect($results->items())->toHaveCount(0);
    });

    it('performs case-insensitive search', function () {
        $results = UserService::listUser('testbuddy');

        expect($results->items())->toHaveCount(1)
            ->and($results->items()[0]['nickname'])->toBe('TestBuddy');

        $resultsUpper = UserService::listUser('TESTBUDDY');
        expect($resultsUpper->items())->toHaveCount(1)
            ->and($resultsUpper->items()[0]['nickname'])->toBe('TestBuddy');
    });

    it('performs partial nickname matching', function () {
        $results = UserService::listUser('Test');

        expect($results->items())->toHaveCount(1)
            ->and($results->items()[0]['nickname'])->toBe('TestBuddy');
    });

    it('returns multiple results when multiple users match', function () {
        User::factory()->create(['nickname' => 'TestUser1']);
        User::factory()->create(['nickname' => 'TestUser2']);

        $results = UserService::listUser('Test');

        expect($results->items())->toHaveCount(3);
    });
});

describe('User Search by Provider Username', function () {
    it('can search by GitHub username', function () {
        $results = UserService::listUser('testbuddy_gh');

        expect($results->items())->toHaveCount(1)
            ->and($results->items()[0]['nickname'])->toBe('TestBuddy');
    });

    it('can search by GitLab username', function () {
        $results = UserService::listUser('codemaster_gl');

        expect($results->items())->toHaveCount(1)
            ->and($results->items()[0]['nickname'])->toBe('CodeMaster');
    });

    it('can search by Bitbucket username', function () {
        $results = UserService::listUser('bughunter_bb');

        expect($results->items())->toHaveCount(1)
            ->and($results->items()[0]['nickname'])->toBe('BugHunter');
    });

    it('performs case-insensitive provider username search', function () {
        $results = UserService::listUser('TESTBUDDY_GH');

        expect($results->items())->toHaveCount(1)
            ->and($results->items()[0]['nickname'])->toBe('TestBuddy');
    });

    it('performs partial provider username matching', function () {
        $results = UserService::listUser('testbuddy');

        expect($results->items())->toHaveCount(1);
    });
});

describe('User Search Results Structure', function () {
    it('includes user id in results', function () {
        $results = UserService::listUser('TestBuddy');

        expect($results->items()[0])->toHaveKey('id')
            ->and($results->items()[0]['id'])->toBeInt();
    });

    it('includes nickname in results', function () {
        $results = UserService::listUser('TestBuddy');

        expect($results->items()[0])->toHaveKey('nickname')
            ->and($results->items()[0]['nickname'])->toBe('TestBuddy');
    });

    it('includes avatar in results', function () {
        $results = UserService::listUser('TestBuddy');

        expect($results->items()[0])->toHaveKey('avatar');
    });

    it('includes full name in results', function () {
        $results = UserService::listUser('TestBuddy');

        expect($results->items()[0])->toHaveKey('name')
            ->and($results->items()[0]['name'])->toBe('Test Buddy User');
    });

    it('includes provider information in results', function () {
        $results = UserService::listUser('TestBuddy');

        expect($results->items()[0])->toHaveKey('providers')
            ->and($results->items()[0]['providers'])->toHaveCount(1);
    });

    it('includes provider details with correct structure', function () {
        $results = UserService::listUser('TestBuddy');
        $provider = $results->items()[0]['providers'][0];

        expect($provider)->toHaveKey('provider')
            ->and($provider)->toHaveKey('provider_username')
            ->and($provider['provider'])->toBe('github')
            ->and($provider['provider_username'])->toBe('testbuddy_gh');
    });

    it('includes multiple providers for users with multiple accounts', function () {
        $results = UserService::listUser('MultiDev');

        expect($results->items()[0]['providers'])->toHaveCount(2);

        $providerNames = collect($results->items()[0]['providers'])
            ->pluck('provider')
            ->toArray();

        expect($providerNames)->toContain('github')
            ->and($providerNames)->toContain('gitlab');
    });
});

describe('User Search Pagination', function () {
    it('paginates results correctly', function () {
        User::factory()->count(25)->create();

        $results = UserService::listUser('');

        expect($results)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class)
            ->and($results->perPage())->toBe(20)
            ->and($results->total())->toBeGreaterThanOrEqual(25);
    });

    it('returns correct page when specified', function () {
        User::factory()->count(25)->create();

        $results = UserService::listUser('', page: 2);

        expect($results->currentPage())->toBe(2);
    });
});

describe('User Search Sorting', function () {
    it('sorts results alphabetically by nickname', function () {
        $results = UserService::listUser('');

        $nicknames = collect($results->items())->pluck('nickname')->toArray();

        expect($nicknames)->toBe(collect($nicknames)->sort()->values()->toArray());
    });
});

<?php

declare(strict_types=1);

use App\Models\Follower;
use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Foundation\Testing\DatabaseTruncation;
uses(DatabaseTruncation::class);


beforeEach(function () {
    $this->authUser = User::factory()->create(['nickname' => 'AuthUser']);
    UserProvider::create([
        'user_id' => $this->authUser->id,
        'provider' => 'github',
        'provider_id' => fake()->numerify('########'),
        'provider_username' => 'authuser_gh',
        'provider_email' => fake()->email(),
        'nickname' => 'authuser_gh',
        'avatar' => fake()->imageUrl(),
        'token' => fake()->sha256(),
        'refresh_token' => fake()->sha256(),
    ]);
    $this->actingAs($this->authUser);

    // Create target users
    $this->targetUser = User::factory()->create(['nickname' => 'TargetUser']);
    UserProvider::create([
        'user_id' => $this->targetUser->id,
        'provider' => 'github',
        'provider_id' => fake()->numerify('########'),
        'provider_username' => 'targetuser_gh',
        'provider_email' => fake()->email(),
        'nickname' => 'targetuser_gh',
        'avatar' => fake()->imageUrl(),
        'token' => fake()->sha256(),
        'refresh_token' => fake()->sha256(),
    ]);

    $this->gitlabUser = User::factory()->create(['nickname' => 'GitLabUser']);
    UserProvider::create([
        'user_id' => $this->gitlabUser->id,
        'provider' => 'gitlab',
        'provider_id' => fake()->numerify('########'),
        'provider_username' => 'gitlabuser_gl',
        'provider_email' => fake()->email(),
        'nickname' => 'gitlabuser_gl',
        'avatar' => fake()->imageUrl(),
        'token' => fake()->sha256(),
        'refresh_token' => fake()->sha256(),
    ]);

    $this->bitbucketUser = User::factory()->create(['nickname' => 'BitbucketUser']);
    UserProvider::create([
        'user_id' => $this->bitbucketUser->id,
        'provider' => 'bitbucket',
        'provider_id' => fake()->numerify('########'),
        'provider_username' => 'bitbucketuser_bb',
        'provider_email' => fake()->email(),
        'nickname' => 'bitbucketuser_bb',
        'avatar' => fake()->imageUrl(),
        'token' => fake()->sha256(),
        'refresh_token' => fake()->sha256(),
    ]);
});

describe('Follow User Functionality', function () {
    it('can follow another user', function () {
        $response = $this->post(route('users.follow', $this->targetUser));

        $response->assertOk();
        expect(Follower::where('user_id', $this->authUser->id)
            ->where('followed_id', $this->targetUser->id)
            ->exists())->toBeTrue();
    });

    it('cannot follow yourself', function () {
        $response = $this->post(route('users.follow', $this->authUser));

        $response->assertStatus(403);
        expect(Follower::where('user_id', $this->authUser->id)
            ->where('followed_id', $this->authUser->id)
            ->exists())->toBeFalse();
    });

    it('can follow user regardless of their provider', function () {
        // Follow GitHub user
        $this->post(route('users.follow', $this->targetUser));
        expect(Follower::where('user_id', $this->authUser->id)
            ->where('followed_id', $this->targetUser->id)
            ->exists())->toBeTrue();

        // Follow GitLab user
        $this->post(route('users.follow', $this->gitlabUser));
        expect(Follower::where('user_id', $this->authUser->id)
            ->where('followed_id', $this->gitlabUser->id)
            ->exists())->toBeTrue();

        // Follow Bitbucket user
        $this->post(route('users.follow', $this->bitbucketUser));
        expect(Follower::where('user_id', $this->authUser->id)
            ->where('followed_id', $this->bitbucketUser->id)
            ->exists())->toBeTrue();
    });

    it('does not create duplicate follow relationships', function () {
        $this->post(route('users.follow', $this->targetUser));

        $this->post(route('users.follow', $this->targetUser));

        expect(Follower::where('user_id', $this->authUser->id)
            ->where('followed_id', $this->targetUser->id)
            ->count())->toBe(1);
    });
});

describe('Unfollow User Functionality', function () {
    it('can unfollow a user', function () {
        // follow
        Follower::create([
            'user_id' => $this->authUser->id,
            'followed_id' => $this->targetUser->id,
        ]);

        //unfollow
        $response = $this->delete(route('users.unfollow', $this->targetUser));

        $response->assertOk();
        expect(Follower::where('user_id', $this->authUser->id)
            ->where('followed_id', $this->targetUser->id)
            ->exists())->toBeFalse();
    });

    it('cannot unfollow yourself', function () {
        $response = $this->delete(route('users.unfollow', $this->authUser));

        $response->assertStatus(403);
    });

    it('can unfollow user regardless of their provider', function () {
        Follower::create(['user_id' => $this->authUser->id, 'followed_id' => $this->targetUser->id]);
        Follower::create(['user_id' => $this->authUser->id, 'followed_id' => $this->gitlabUser->id]);
        Follower::create(['user_id' => $this->authUser->id, 'followed_id' => $this->bitbucketUser->id]);

        // Unfollow GitHub user
        $this->delete(route('users.unfollow', $this->targetUser));
        expect(Follower::where('user_id', $this->authUser->id)
            ->where('followed_id', $this->targetUser->id)
            ->exists())->toBeFalse();

        // Unfollow GitLab user
        $this->delete(route('users.unfollow', $this->gitlabUser));
        expect(Follower::where('user_id', $this->authUser->id)
            ->where('followed_id', $this->gitlabUser->id)
            ->exists())->toBeFalse();

        // Unfollow Bitbucket user
        $this->delete(route('users.unfollow', $this->bitbucketUser));
        expect(Follower::where('user_id', $this->authUser->id)
            ->where('followed_id', $this->bitbucketUser->id)
            ->exists())->toBeFalse();
    });
});

describe('Follower Count Updates', function () {
    it('updates follower count correctly when following', function () {
        $initialCount = $this->targetUser->followers()->count();

        $this->post(route('users.follow', $this->targetUser));

        $this->targetUser->refresh();
        expect($this->targetUser->followers()->count())->toBe($initialCount + 1);
    });

    it('updates follower count correctly when unfollowing', function () {
        Follower::create([
            'user_id' => $this->authUser->id,
            'followed_id' => $this->targetUser->id,
        ]);

        $initialCount = $this->targetUser->followers()->count();

        $this->delete(route('users.unfollow', $this->targetUser));

        $this->targetUser->refresh();
        expect($this->targetUser->followers()->count())->toBe($initialCount - 1);
    });

    it('maintains correct follower count regardless of provider', function () {
        $follower2 = User::factory()->create();
        $follower3 = User::factory()->create();

        Follower::create(['user_id' => $this->authUser->id, 'followed_id' => $this->targetUser->id]);
        Follower::create(['user_id' => $follower2->id, 'followed_id' => $this->targetUser->id]);
        Follower::create(['user_id' => $follower3->id, 'followed_id' => $this->targetUser->id]);

        expect($this->targetUser->followers()->count())->toBe(3);
    });
});

describe('Follow Authentication', function () {
    it('requires authentication to follow a user', function () {
        auth()->logout();

        $response = $this->post(route('users.follow', $this->targetUser));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    });

    it('requires authentication to unfollow a user', function () {
        auth()->logout();

        $response = $this->delete(route('users.unfollow', $this->targetUser));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    });
});

describe('Following Relationships', function () {
    it('can retrieve list of users a user is following', function () {
        Follower::create(['user_id' => $this->authUser->id, 'followed_id' => $this->targetUser->id]);
        Follower::create(['user_id' => $this->authUser->id, 'followed_id' => $this->gitlabUser->id]);

        $followings = $this->authUser->followings;

        expect($followings)->toHaveCount(2)
            ->and($followings->pluck('id')->toArray())->toContain($this->targetUser->id)
            ->and($followings->pluck('id')->toArray())->toContain($this->gitlabUser->id);
    });

    it('can retrieve list of followers for a user', function () {
        $follower2 = User::factory()->create();
        $follower3 = User::factory()->create();

        Follower::create(['user_id' => $this->authUser->id, 'followed_id' => $this->targetUser->id]);
        Follower::create(['user_id' => $follower2->id, 'followed_id' => $this->targetUser->id]);
        Follower::create(['user_id' => $follower3->id, 'followed_id' => $this->targetUser->id]);

        $followers = $this->targetUser->followers;

        expect($followers)->toHaveCount(3);
    });
});

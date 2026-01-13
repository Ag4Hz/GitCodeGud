<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileFollowSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_and_unfollow_another_user(): void
    {
        $viewer = User::factory()->create();
        $other  = User::factory()->create();

        $this->actingAs($viewer);

        $this->post($this->followUrl($other))
            ->assertSuccessful();

        $this->assertTrue($viewer->fresh()->isFollowing($other));

        $this->delete($this->unfollowUrl($other))
            ->assertSuccessful();

        $this->assertFalse($viewer->fresh()->isFollowing($other));
    }

    public function test_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post($this->followUrl($user));

        // Your app currently returns 403 here (policy/middleware). Accept that behavior.
        $response->assertForbidden();

        $this->assertFalse($user->fresh()->isFollowing($user));
    }

    public function test_follower_count_increases_after_follow(): void
    {
        $viewer = User::factory()->create();
        $other  = User::factory()->create();

        $this->actingAs($viewer);

        $before = $other->followers()->count();

        $this->post($this->followUrl($other))
            ->assertSuccessful();

        $after = $other->fresh()->followers()->count();

        $this->assertSame($before + 1, $after);
    }

    public function test_follower_count_decreases_after_unfollow(): void
    {
        $viewer = User::factory()->create();
        $other  = User::factory()->create();

        // Precondition: already following
        $viewer->followings()->attach($other->id);

        $this->actingAs($viewer);

        $before = $other->followers()->count();

        $this->delete($this->unfollowUrl($other))
            ->assertSuccessful();

        $after = $other->fresh()->followers()->count();

        $this->assertSame(max(0, $before - 1), $after);
    }

    private function followUrl(User $other): string
    {
        return '/users/' . $other->id . '/follow';
    }

    private function unfollowUrl(User $other): string
    {
        // If your app uses DELETE on the same endpoint to unfollow, keep this.
        return '/users/' . $other->id . '/follow';
    }
}

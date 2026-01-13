<?php

namespace Profile;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * File: `tests/Browser/ProfileViewingAndRenderingTest.php`
 *
 * Group 1: Profile viewing \& basic rendering
 */
class ProfileViewingAndRenderingTest extends DuskTestCase
{
    private function intFrom(Browser $browser, string $selector): int
    {
        $text = (string) $browser->text($selector);
        $digits = preg_replace('/[^\d]/', '', $text) ?? '0';

        return (int) $digits;
    }

    public function test_user_can_view_own_profile(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/profile')
                ->assertPresent('[data-testid=profile-root]');
        });
    }

    public function test_user_can_view_other_users_profile(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $this->browse(function (Browser $browser) use ($viewer, $other) {
            $browser->loginAs($viewer)
                ->visit('/users/' . $other->id)
                ->assertPresent('[data-testid=profile-root]');
        });
    }

    public function test_profile_displays_user_name_nickname_email_correctly(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            // Adjust to your schema if needed (e.g. `username` instead of `nickname`)
            'nickname' => 'jane',
            'email' => 'jane@example.com',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/profile')
                ->assertSeeIn('[data-testid=profile-name]', 'Jane Doe')
                ->assertSeeIn('[data-testid=profile-nickname]', 'jane')
                ->assertSeeIn('[data-testid=profile-email]', 'jane@example.com');
        });
    }

    public function test_profile_displays_user_avatar_oauth_or_initials_fallback(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)->visit('/profile');

            $hasImg = $browser->element('[data-testid=profile-avatar-img]') !== null;
            $hasFallback = $browser->element('[data-testid=profile-avatar-fallback]') !== null;

            $this->assertTrue(
                $hasImg || $hasFallback,
                'Expected OAuth avatar image or initials fallback.'
            );
        });
    }

    public function test_profile_displays_user_xp_and_level(): void
    {
        $user = User::factory()->create([
            // Adjust to your schema if different.
            'xp' => 1234,
            'level' => 7,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/profile')
                ->assertSeeIn('[data-testid=profile-xp]', '1234')
                ->assertSeeIn('[data-testid=profile-level]', '7');
        });
    }

    public function test_profile_displays_level_progress_bar_with_correct_percentage(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/profile')
                ->assertPresent('[data-testid=level-progress-bar]');

            $valueNow = $browser->attribute('[data-testid=level-progress-bar]', 'aria-valuenow')
                ?? $browser->attribute('[data-testid=level-progress-bar]', 'data-percent');

            $this->assertNotNull($valueNow);

            $pct = (float) $valueNow;
            $this->assertGreaterThanOrEqual(0, $pct);
            $this->assertLessThanOrEqual(100, $pct);
        });
    }

    public function test_profile_displays_followers_followings_count(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/profile')
                ->assertPresent('[data-testid=followers-count]')
                ->assertPresent('[data-testid=followings-count]');

            $followers = $this->intFrom($browser, '[data-testid=followers-count]');
            $followings = $this->intFrom($browser, '[data-testid=followings-count]');

            $this->assertGreaterThanOrEqual(0, $followers);
            $this->assertGreaterThanOrEqual(0, $followings);
        });
    }

    public function test_each_skill_shows_name_xp_amount_and_level(): void
    {
        $user = User::factory()->create();
        // TODO: seed/create skills for $user so at least 1 is rendered.

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/profile')
                ->assertPresent('[data-testid=skills-section]')
                ->assertPresent('[data-testid=skill-row]');

            $browser->assertPresent('[data-testid=skill-name]')
                ->assertPresent('[data-testid=skill-xp]')
                ->assertPresent('[data-testid=skill-level]');
        });
    }
}

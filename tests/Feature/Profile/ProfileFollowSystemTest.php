<?php

namespace Profile;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * File: `tests/Browser/ProfileFollowSystemTest.php`
 *
 * Group 3: Follow system behavior
 */
class ProfileFollowSystemTest extends DuskTestCase
{
    private function intFrom(Browser $browser, string $selector): int
    {
        $text = (string) $browser->text($selector);
        $digits = preg_replace('/[^\d]/', '', $text) ?? '0';

        return (int) $digits;
    }

    public function test_user_can_follow_unfollow_another_user(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $this->browse(function (Browser $browser) use ($viewer, $other) {
            $browser->loginAs($viewer)
                ->visit('/users/' . $other->id)
                ->assertPresent('[data-testid=follow-button]')
                ->click('[data-testid=follow-button]')
                ->waitForText('Unfollow', 5)
                ->click('[data-testid=follow-button]')
                ->waitForText('Follow', 5);
        });
    }

    public function test_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/profile')
                ->assertMissing('[data-testid=follow-button]');
        });
    }

    public function test_follow_button_changes_to_unfollow_after_following(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $this->browse(function (Browser $browser) use ($viewer, $other) {
            $browser->loginAs($viewer)
                ->visit('/users/' . $other->id)
                ->assertPresent('[data-testid=follow-button]')
                ->assertSeeIn('[data-testid=follow-button]', 'Follow')
                ->click('[data-testid=follow-button]')
                ->waitForText('Unfollow', 5)
                ->assertSeeIn('[data-testid=follow-button]', 'Unfollow');
        });
    }

    public function test_follower_count_increases_after_follow(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $this->browse(function (Browser $browser) use ($viewer, $other) {
            $browser->loginAs($viewer)
                ->visit('/users/' . $other->id)
                ->assertPresent('[data-testid=followers-count]')
                ->assertPresent('[data-testid=follow-button]');

            $before = $this->intFrom($browser, '[data-testid=followers-count]');

            $browser->click('[data-testid=follow-button]')
                ->waitForText('Unfollow', 5);

            $after = $this->intFrom($browser, '[data-testid=followers-count]');

            $this->assertGreaterThanOrEqual($before + 1, $after);
        });
    }

    public function test_follower_count_decreases_after_unfollow(): void
    {
        $viewer = User::factory()->create();
        $other = User::factory()->create();

        $this->browse(function (Browser $browser) use ($viewer, $other) {
            $browser->loginAs($viewer)
                ->visit('/users/' . $other->id)
                ->assertPresent('[data-testid=followers-count]')
                ->assertPresent('[data-testid=follow-button]');

            if (stripos((string) $browser->text('[data-testid=follow-button]'), 'Follow') !== false) {
                $browser->click('[data-testid=follow-button]')
                    ->waitForText('Unfollow', 5);
            }

            $before = $this->intFrom($browser, '[data-testid=followers-count]');

            $browser->click('[data-testid=follow-button]')
                ->waitForText('Follow', 5);

            $after = $this->intFrom($browser, '[data-testid=followers-count]');

            $this->assertLessThanOrEqual($before - 1, $after);
        });
    }
}

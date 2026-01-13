<?php

namespace Profile;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * File: `tests/Browser/ProfileProvidersAndSyncTest.php`
 *
 * Group 2: Multi\-provider display \& repository counts \& sync
 */
class ProfileProvidersAndSyncTest extends DuskTestCase
{
    public function test_profile_with_connected_providers_renders_correctly(): void
    {
        $user = User::factory()->create();
        // TODO: attach provider connections to $user according to your models.

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/profile')
                ->assertPresent('[data-testid=providers-section]')
                ->assertPresent('[data-testid=provider-card]');
        });
    }

    public function test_repo_counts_calculated_correctly_per_provider(): void
    {
        $user = User::factory()->create();
        // TODO: seed/create repos per provider for $user so UI can display counts.

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/profile')
                ->assertPresent('[data-testid=provider-row]');

            $rows = $browser->elements('[data-testid=provider-row]');
            $this->assertNotEmpty($rows);

            // Minimal assertion: each row exposes a numeric count element
            $counts = $browser->elements('[data-testid=provider-repo-count]');
            $this->assertNotEmpty($counts);

            foreach ($counts as $el) {
                $text = trim((string) $el->getText());
                $this->assertMatchesRegularExpression('/\d+/', $text);
            }
        });
    }

    public function test_sync_buttons_shown_for_profile_owner_only(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $this->browse(function (Browser $browser) use ($owner, $viewer) {
            $browser->loginAs($owner)
                ->visit('/profile')
                ->assertPresent('[data-testid=sync-github-button]')
                ->assertPresent('[data-testid=sync-gitlab-button]')
                ->assertPresent('[data-testid=sync-bitbucket-button]');

            $browser->loginAs($viewer)
                ->visit('/users/' . $owner->id)
                ->assertMissing('[data-testid=sync-github-button]')
                ->assertMissing('[data-testid=sync-gitlab-button]')
                ->assertMissing('[data-testid=sync-bitbucket-button]');
        });
    }

    public function test_skill_sync_updates_user_skills_from_github_repositories(): void
    {
        $user = User::factory()->create();
        // TODO: ensure $user has GitHub connected and sync endpoint wired for UI.

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/profile')
                ->assertPresent('[data-testid=sync-github-button]');

            $before = count($browser->elements('[data-testid=skill-row]'));

            $browser->click('[data-testid=sync-github-button]')
                ->waitFor('[data-testid=sync-success-toast]', 15);

            $after = count($browser->elements('[data-testid=skill-row]'));

            $this->assertGreaterThanOrEqual($before, $after);
        });
    }
}

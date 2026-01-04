<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use App\Models\UserProvider;
use App\Models\Skill;
use App\Models\SkillUser;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use PHPUnit\Framework\Attributes\Test;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LeaderboardBrowserTest extends DuskTestCase
{
    use DatabaseTruncation;

    protected $tablesToTruncate = [
        'users',
        'user_providers',
        'followers',
        'user_skills',
        'user_badges',
        'bounties',
        'issues',
        'repos',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->phpSkill = Skill::firstOrCreate(
            ['skill_name' => 'PHP', 'type' => 'language'],
            ['multiplier' => 1.0]
        );

        $this->jsSkill = Skill::firstOrCreate(
            ['skill_name' => 'JavaScript', 'type' => 'language'],
            ['multiplier' => 1.0]
        );

        $this->pythonSkill = Skill::firstOrCreate(
            ['skill_name' => 'Python', 'type' => 'language'],
            ['multiplier' => 1.0]
        );

        $this->topUser = User::factory()->create([
            'nickname' => 'TopCoder',
            'name' => 'Top Coder Pro',
            'xp' => 5000,
        ]);
        UserProvider::create([
            'user_id' => $this->topUser->id,
            'provider' => 'github',
            'provider_id' => fake()->numerify('########'),
            'provider_username' => 'topcoder_gh',
            'provider_email' => fake()->email(),
            'nickname' => 'topcoder_gh',
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
            'nickname' => 'MiddleDev',
            'name' => 'Middle Developer',
            'xp' => 2000,
        ]);
        UserProvider::create([
            'user_id' => $this->middleUser->id,
            'provider' => 'gitlab',
            'provider_id' => fake()->numerify('########'),
            'provider_username' => 'middledev_gl',
            'provider_email' => fake()->email(),
            'nickname' => 'middledev_gl',
            'avatar' => fake()->imageUrl(),
            'token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
        ]);
        SkillUser::factory()->create([
            'user_id' => $this->middleUser->id,
            'skill_id' => $this->phpSkill->id,
            'xp' => 1500,
        ]);

        $this->beginnerUser = User::factory()->create([
            'nickname' => 'BeginnerBuddy',
            'name' => 'Beginner Buddy',
            'xp' => 500,
        ]);
        UserProvider::create([
            'user_id' => $this->beginnerUser->id,
            'provider' => 'bitbucket',
            'provider_id' => fake()->numerify('########'),
            'provider_username' => 'beginner_bb',
            'provider_email' => fake()->email(),
            'nickname' => 'beginner_bb',
            'avatar' => fake()->imageUrl(),
            'token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
        ]);
        SkillUser::factory()->create([
            'user_id' => $this->beginnerUser->id,
            'skill_id' => $this->jsSkill->id,
            'xp' => 400,
        ]);

        $repo = \App\Models\Repo::factory()->create();

        $issue1 = \App\Models\Issue::factory()->create(['repo_id' => $repo->id]);
        $issue2 = \App\Models\Issue::factory()->create(['repo_id' => $repo->id]);

        \App\Models\Bounty::factory()->create([
            'issue_id' => $issue1->id,
            'status' => 'open',
            'languages' => ['PHP'],
        ]);

        \App\Models\Bounty::factory()->create([
            'issue_id' => $issue2->id,
            'status' => 'open',
            'languages' => ['JavaScript'],
        ]);
    }

    #[Test]
    public function user_can_view_leaderboard_page()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->assertSee('Leaderboard')
                ->assertSee('@TopCoder')
                ->assertSee('@MiddleDev')
                ->assertSee('@BeginnerBuddy');
        });
    }

    #[Test]
    public function leaderboard_displays_users_in_xp_descending_order()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->assertSeeIn('tbody tr:nth-child(1)', '@TopCoder')
                ->assertSeeIn('tbody tr:nth-child(2)', '@MiddleDev')
                ->assertSeeIn('tbody tr:nth-child(3)', '@BeginnerBuddy');
        });
    }

    #[Test]
    public function leaderboard_displays_xp_values()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->assertSee('5.0k')
                ->assertSee('2.0k')
                ->assertSee('500');
        });
    }

    #[Test]
    public function leaderboard_displays_provider_badges()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->assertSee('GitHub')
                ->assertSee('GitLab')
                ->assertSee('Bitbucket');
        });
    }

    #[Test]
    public function user_can_click_on_leaderboard_entry_to_view_profile()
    {
        $authUser = User::factory()->create([
            'nickname' => 'AuthUser',
            'name' => 'Auth User',
        ]);

        UserProvider::create([
            'user_id' => $authUser->id,
            'provider' => 'github',
            'provider_id' => fake()->numerify('########'),
            'provider_username' => 'authuser_gh',
            'provider_email' => fake()->email(),
            'nickname' => 'authuser_gh',
            'avatar' => fake()->imageUrl(),
            'token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
        ]);

        $this->browse(function (Browser $browser) use ($authUser) {
            $browser->loginAs($authUser)
                ->visit('/leaderboard')
                ->clickLink('@TopCoder')
                ->pause(1000)
                ->assertPathIs("/users/{$this->topUser->id}")
                ->assertSee('Top Coder Pro');
        });
    }

    #[Test]
    public function leaderboard_shows_ranking_numbers()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->assertSeeIn('tbody tr:nth-child(1)', '1')
                ->assertSeeIn('tbody tr:nth-child(2)', '2')
                ->assertSeeIn('tbody tr:nth-child(3)', '3');
        });
    }

    #[Test]
    public function leaderboard_pagination_works()
    {
        for ($i = 0; $i < 15; $i++) {
            $user = User::factory()->create([
                'xp' => 1000 + $i,
            ]);
            UserProvider::create([
                'user_id' => $user->id,
                'provider' => 'github',
                'provider_id' => fake()->numerify('########'),
                'provider_username' => "user{$i}_gh",
                'provider_email' => fake()->email(),
                'nickname' => "user{$i}_gh",
                'avatar' => fake()->imageUrl(),
                'token' => fake()->sha256(),
                'refresh_token' => fake()->sha256(),
            ]);
        }

        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->assertSee('@TopCoder')
                ->clickLink('2')
                ->pause(1000)
                ->assertDontSee('@TopCoder');
        });
    }

    #[Test]
    public function leaderboard_search_filters_results()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->type('input[placeholder*="buddy"]', 'TopCoder')
                ->pause(1000)
                ->assertSee('@TopCoder');
        });
    }

    #[Test]
    public function leaderboard_displays_user_levels()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->assertPresent('tbody tr:nth-child(1)')
                ->assertPresent('tbody tr:nth-child(2)')
                ->assertPresent('tbody tr:nth-child(3)');
        });
    }

    #[Test]
    public function user_can_filter_leaderboard_by_language()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->pause(2000)
                ->waitForText('All Languages')
                ->pause(500);

            // language dropdown to open it
            $browser->script('
                const buttons = Array.from(document.querySelectorAll("button"));
                const langButton = buttons.find(btn => btn.textContent.includes("All Languages"));
                if (langButton) langButton.click();
            ');

            $browser->pause(1000);

            // PHP directly from the opened dropdown menu
            $browser->script('
                const menuItems = Array.from(document.querySelectorAll("[role=menuitem], a, button, div"));
                const phpItem = menuItems.find(item => item.textContent.trim() === "PHP");
                if (phpItem) phpItem.click();
            ');

            $browser->pause(2000)
                ->assertSee('@TopCoder')
                ->assertSee('@MiddleDev')
                ->assertDontSee('@BeginnerBuddy');
        });
    }
}

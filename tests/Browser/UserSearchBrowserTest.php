<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use PHPUnit\Framework\Attributes\Test;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class UserSearchBrowserTest extends DuskTestCase
{
    use DatabaseTruncation;

    protected $tablesToTruncate = [
        'users',
        'user_providers',
        'followers',
        'user_skills',
        'user_badges',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create([
            'nickname' => 'SearchableBuddy',
            'name' => 'Searchable Buddy User',
        ]);
        UserProvider::create([
            'user_id' => $this->user1->id,
            'provider' => 'github',
            'provider_id' => fake()->numerify('########'),
            'provider_username' => 'searchable_gh',
            'provider_email' => fake()->email(),
            'nickname' => 'searchable_gh',
            'avatar' => fake()->imageUrl(),
            'token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
        ]);

        $this->user2 = User::factory()->create([
            'nickname' => 'TestDeveloper',
            'name' => 'Test Developer Pro',
        ]);
        UserProvider::create([
            'user_id' => $this->user2->id,
            'provider' => 'gitlab',
            'provider_id' => fake()->numerify('########'),
            'provider_username' => 'testdev_gl',
            'provider_email' => fake()->email(),
            'nickname' => 'testdev_gl',
            'avatar' => fake()->imageUrl(),
            'token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
        ]);

        $this->user3 = User::factory()->create([
            'nickname' => 'CodeNinja',
            'name' => 'Code Ninja Master',
        ]);
        UserProvider::create([
            'user_id' => $this->user3->id,
            'provider' => 'bitbucket',
            'provider_id' => fake()->numerify('########'),
            'provider_username' => 'codeninja_bb',
            'provider_email' => fake()->email(),
            'nickname' => 'codeninja_bb',
            'avatar' => fake()->imageUrl(),
            'token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
        ]);
    }

    #[Test]
    public function user_can_search_for_other_users_by_nickname()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->type('input[placeholder*="buddy"]', 'SearchableBuddy')
                ->pause(1000)
                ->assertSee('@SearchableBuddy')
                ->assertSee('Searchable Buddy User');
        });
    }

    #[Test]
    public function search_is_case_insensitive()
    {
        $this->browse(function (Browser $browser) {
            // Kisbetu
            $browser->visit('/leaderboard')
                ->type('input[placeholder*="buddy"]', 'searchablebuddy')
                ->pause(1000)
                ->assertSee('@SearchableBuddy');

            // Nagybetu
            $browser->refresh()
                ->type('input[placeholder*="buddy"]', 'SEARCHABLEBUDDY')
                ->pause(1000)
                ->assertSee('@SearchableBuddy');

            // Vegyes teszt
            $browser->refresh()
                ->type('input[placeholder*="buddy"]', 'SeArChAbLeBuDdY')
                ->pause(1000)
                ->assertSee('@SearchableBuddy');
        });
    }

    #[Test]
    public function user_can_search_by_github_username()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->type('input[placeholder*="buddy"]', 'searchable_gh')
                ->pause(1000)
                ->assertSee('@SearchableBuddy');
        });
    }

    #[Test]
    public function user_can_search_by_gitlab_username()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->type('input[placeholder*="buddy"]', 'testdev_gl')
                ->pause(1000)
                ->assertSee('@TestDeveloper');
        });
    }

    #[Test]
    public function user_can_search_by_bitbucket_username()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->type('input[placeholder*="buddy"]', 'codeninja_bb')
                ->pause(1000)
                ->assertSee('@CodeNinja');
        });
    }

    #[Test]
    public function search_shows_no_buddies_found_when_no_match()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->type('input[placeholder*="buddy"]', 'NonExistentUser12345')
                ->pause(1000)
                ->assertDontSee('@NonExistentUser12345');
        });
    }

    #[Test]
    public function clicking_search_result_navigates_to_user_profile()
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
                ->type('input[placeholder*="buddy"]', 'SearchableBuddy')
                ->pause(1000)
                ->assertSee('@SearchableBuddy')
                ->clickLink('@SearchableBuddy')
                ->pause(1000)
                ->assertPathIs("/users/{$this->user1->id}");
        });
    }

    #[Test]
    public function search_input_does_not_clear_when_navigating()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->type('input[placeholder*="buddy"]', 'SearchableBuddy')
                ->pause(1000)
                ->assertSee('@SearchableBuddy')
                ->assertInputValue('input[placeholder*="buddy"]', 'SearchableBuddy');
        });
    }

    #[Test]
    public function search_can_be_performed_multiple_times()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/leaderboard')
                ->type('input[placeholder*="buddy"]', 'SearchableBuddy')
                ->pause(1000)
                ->assertSee('@SearchableBuddy');

            $browser->visit('/leaderboard')
                ->type('input[placeholder*="buddy"]', 'TestDeveloper')
                ->pause(1000)
                ->assertSee('@TestDeveloper');
        });
    }
}

<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use PHPUnit\Framework\Attributes\Test;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class FollowBrowserTest extends DuskTestCase
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

        $this->authUser = User::factory()->create([
            'nickname' => 'AuthUser',
            'name' => 'AuthUser',
            'email' => 'authuser@example.com',
            'password' => bcrypt('password'),
            'xp' => 500,
        ]);

        $provider = new UserProvider();
        $provider->user_id = $this->authUser->id;
        $provider->provider = 'github';
        $provider->provider_id = '12345001';
        $provider->provider_username = 'authuser_gh';
        $provider->provider_email = 'auth@example.com';
        $provider->nickname = 'authuser_gh';
        $provider->avatar = 'https://via.placeholder.com/150';
        $provider->token = hash('sha256', 'token1');
        $provider->refresh_token = hash('sha256', 'refresh1');
        $provider->save();

        $this->githubUser = User::factory()->create([
            'nickname' => 'GitHubUser',
            'name' => 'GitHubUser',
            'xp' => 1000,
        ]);

        $githubProvider = new UserProvider();
        $githubProvider->user_id = $this->githubUser->id;
        $githubProvider->provider = 'github';
        $githubProvider->provider_id = '12345002';
        $githubProvider->provider_username = 'githubuser';
        $githubProvider->provider_email = 'github@example.com';
        $githubProvider->nickname = 'githubuser';
        $githubProvider->avatar = 'https://via.placeholder.com/150';
        $githubProvider->token = hash('sha256', 'token2');
        $githubProvider->refresh_token = hash('sha256', 'refresh2');
        $githubProvider->save();

        $this->gitlabUser = User::factory()->create([
            'nickname' => 'GitLabUser',
            'name' => 'GitLabUser',
            'xp' => 1000,
        ]);

        $gitlabProvider = new UserProvider();
        $gitlabProvider->user_id = $this->gitlabUser->id;
        $gitlabProvider->provider = 'gitlab';
        $gitlabProvider->provider_id = '12345003';
        $gitlabProvider->provider_username = 'gitlabuser';
        $gitlabProvider->provider_email = 'gitlab@example.com';
        $gitlabProvider->nickname = 'gitlabuser';
        $gitlabProvider->avatar = 'https://via.placeholder.com/150';
        $gitlabProvider->token = hash('sha256', 'token3');
        $gitlabProvider->refresh_token = hash('sha256', 'refresh3');
        $gitlabProvider->save();

        $this->bitbucketUser = User::factory()->create([
            'nickname' => 'BitbucketUser',
            'name' => 'BitbucketUser',
            'xp' => 1000,
        ]);

        $bitbucketProvider = new UserProvider();
        $bitbucketProvider->user_id = $this->bitbucketUser->id;
        $bitbucketProvider->provider = 'bitbucket';
        $bitbucketProvider->provider_id = '12345004';
        $bitbucketProvider->provider_username = 'bitbucketuser';
        $bitbucketProvider->provider_email = 'bitbucket@example.com';
        $bitbucketProvider->nickname = 'bitbucketuser';
        $bitbucketProvider->avatar = 'https://via.placeholder.com/150';
        $bitbucketProvider->token = hash('sha256', 'token4');
        $bitbucketProvider->refresh_token = hash('sha256', 'refresh4');
        $bitbucketProvider->save();

    }

    #[Test]
    public function user_can_follow_github_user_from_profile()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->authUser)
                ->visit("/users/{$this->githubUser->id}")
                ->waitForText('GitHubUser')
                ->pause(2000)
                ->click('button.bg-green-600')
                ->pause(3000)
                ->assertPresent('button.bg-gray-200')
                ->assertMissing('button.bg-green-600');
        });
    }

    #[Test]
    public function user_can_follow_gitlab_user_from_profile()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->authUser)
                ->visit("/users/{$this->gitlabUser->id}")
                ->waitForText('GitLabUser')
                ->pause(2000)
                ->click('button.bg-green-600')
                ->pause(3000)
                ->assertPresent('button.bg-gray-200')
                ->assertMissing('button.bg-green-600');
        });
    }

    #[Test]
    public function user_can_follow_bitbucket_user_from_profile()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->authUser)
                ->visit("/users/{$this->bitbucketUser->id}")
                ->waitForText('BitbucketUser')
                ->pause(2000)
                ->click('button.bg-green-600')
                ->pause(3000)
                ->assertPresent('button.bg-gray-200')
                ->assertMissing('button.bg-green-600');
        });
    }

    #[Test]
    public function user_can_follow_another_user_and_followers_count_increases()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->authUser)
                ->visit("/users/{$this->githubUser->id}")
                ->waitForText('GitHubUser')
                ->pause(2000)
                ->click('button.bg-green-600')
                ->pause(3000)
                ->assertPresent('button.bg-gray-200')
                ->assertMissing('button.bg-green-600');
        });
    }

    #[Test]
    public function user_can_unfollow_another_user_and_followers_count_decreases()
    {
        $this->authUser->followings()->attach($this->githubUser->id);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->authUser)
                ->visit("/users/{$this->githubUser->id}")
                ->waitForText('GitHubUser')
                ->pause(2000)
                ->assertPresent('button.bg-gray-200')
                ->assertMissing('button.bg-green-600')
                ->click('button.bg-gray-200')
                ->pause(3000)
                ->assertPresent('button.bg-green-600')
                ->assertMissing('button.bg-gray-200');
        });
    }

    #[Test]
    public function follow_button_displays_when_not_following()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->authUser)
                ->visit("/users/{$this->githubUser->id}")
                ->waitForText('GitHubUser')
                ->pause(2000)
                ->assertPresent('button.bg-green-600')
                ->assertMissing('button.bg-gray-200');
        });
    }

    #[Test]
    public function unfollow_button_displays_when_already_following()
    {
        $this->authUser->followings()->attach($this->githubUser->id);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->authUser)
                ->visit("/users/{$this->githubUser->id}")
                ->waitForText('GitHubUser')
                ->pause(2000)
                ->assertPresent('button.bg-gray-200')
                ->assertMissing('button.bg-green-600');
        });
    }

    #[Test]
    public function cannot_follow_yourself_no_buttons_shown()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->authUser)
                ->visit("/users/{$this->authUser->id}")
                ->waitForText('AuthUser')
                ->pause(2000)
                ->assertMissing('button.bg-green-600')
                ->assertMissing('button.bg-gray-200');
        });
    }

    #[Test]
    public function follow_state_persists_after_page_reload()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->authUser)
                ->visit("/users/{$this->githubUser->id}")
                ->waitForText('GitHubUser')
                ->pause(2000)
                ->click('button.bg-green-600')
                ->pause(3000)
                ->assertPresent('button.bg-gray-200')
                ->refresh()
                ->waitForText('GitHubUser')
                ->pause(2000)
                ->assertPresent('button.bg-gray-200')
                ->assertMissing('button.bg-green-600');
        });
    }

    #[Test]
    public function follow_button_works_across_page_navigation()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->authUser)
                ->visit("/users/{$this->githubUser->id}")
                ->waitForText('GitHubUser')
                ->pause(2000)
                ->assertPresent('button.bg-green-600')
                ->click('button.bg-green-600')
                ->pause(3000)
                ->assertPresent('button.bg-gray-200')
                ->visit("/users/{$this->authUser->id}")
                ->waitForText('AuthUser')
                ->pause(2000)
                ->visit("/users/{$this->githubUser->id}")
                ->waitForText('GitHubUser')
                ->pause(2000)
                ->assertPresent('button.bg-gray-200')
                ->assertMissing('button.bg-green-600');
        });
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProvider;
use App\Models\Bounty;
use App\Models\Issue;
use App\Models\Repo;
use App\Models\Submission;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseTruncation;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'nickname' => 'AuthUser',
            'name' => 'Authenticated User',
        ]);

        UserProvider::create([
            'user_id' => $this->user->id,
            'provider' => 'github',
            'provider_id' => fake()->numerify('########'),
            'provider_username' => 'auth_gh',
            'provider_email' => fake()->email(),
            'nickname' => 'auth_gh',
            'avatar' => fake()->imageUrl(),
            'token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
        ]);
    }

    public function test_allows_authenticated_users_to_access_dashboard()
    {
        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
            );
    }

    public function test_redirects_unauthenticated_users_to_login()
    {
        $this->get('/dashboard')
            ->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    public function test_displays_bounty_list_paginated_10_per_page()
    {
        $creator = User::factory()->create();
        $repo = Repo::factory()->create(['user_id' => $creator->id]);

        $issues = Issue::factory()->count(15)->create(['repo_id' => $repo->id]);

        foreach ($issues as $issue) {
            Bounty::factory()->create([
                'issue_id' => $issue->id,
                'title' => 'Bounty Title',
                'status' => 'open',
                'languages' => ['PHP'],
                'reward_xp' => 100,
            ]);
        }

        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('bounties.data', 12)
                ->has('bounties.links')
            );
    }

    public function test_shows_correct_bounty_details()
    {
        $creator = User::factory()->create(['nickname' => 'CreatorDev']);
        $repo = Repo::factory()->create(['name' => 'laravel/framework', 'user_id' => $creator->id]);
        $issue = Issue::factory()->create(['repo_id' => $repo->id]);

        $bounty = Bounty::factory()->create([
            'issue_id' => $issue->id,
            'title' => 'Fix High Severity Bug',
            'status' => 'open',
            'reward_xp' => 500,
            'languages' => ['PHP'],
        ]);

        Submission::factory()->count(3)->create(['bounty_id' => $bounty->id]);

        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('bounties.data.0.title', 'Fix High Severity Bug')
                ->where('bounties.data.0.reward_xp', 500)
                ->where('bounties.data.0.issue.repo.name', 'laravel/framework')
                ->has('bounties.data.0.languages', 1)
                ->where('bounties.data.0.languages.0', 'PHP')
            );
    }

    public function test_filters_bounty_list_by_language_and_dropdown_available()
    {
        $creator = User::factory()->create();
        $repo = Repo::factory()->create(['user_id' => $creator->id]);

        $issue1 = Issue::factory()->create(['repo_id' => $repo->id]);
        $issue2 = Issue::factory()->create(['repo_id' => $repo->id]);

        Bounty::factory()->create(['issue_id' => $issue1->id, 'title' => 'PHP Task', 'status' => 'open', 'languages' => ['PHP'], 'reward_xp' => 100]);
        Bounty::factory()->create(['issue_id' => $issue2->id, 'title' => 'JS Task', 'status' => 'open', 'languages' => ['JavaScript'], 'reward_xp' => 100]);

        $this->actingAs($this->user)
            ->get('/dashboard?language=PHP')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('bounties.data', 1)
                ->where('bounties.data.0.title', 'PHP Task')
                ->has('availableLanguages')
                ->where('filters.language', 'PHP')
            );
    }

    public function test_filters_by_keyword_search_title_and_description()
    {
        $this->withoutExceptionHandling();

        $creator = User::factory()->create();
        $repo = Repo::factory()->create(['user_id' => $creator->id]);

        $issue1 = Issue::factory()->create(['repo_id' => $repo->id]);
        Bounty::factory()->create(['issue_id' => $issue1->id, 'title' => 'UniqueTitleSearch', 'status' => 'open', 'languages' => ['PHP'], 'reward_xp' => 100]);

        $issue2 = Issue::factory()->create(['repo_id' => $repo->id]);
        Bounty::factory()->create(['issue_id' => $issue2->id, 'title' => 'Other Task', 'description' => 'HiddenDescSearch', 'status' => 'open', 'languages' => ['PHP'], 'reward_xp' => 100]);

        $issue3 = Issue::factory()->create(['repo_id' => $repo->id]);
        Bounty::factory()->create(['issue_id' => $issue3->id, 'title' => 'Irrelevant Task', 'status' => 'open', 'languages' => ['PHP'], 'reward_xp' => 100]);

        try {
            $this->actingAs($this->user)
                ->get('/dashboard?search=UniqueTitleSearch')
                ->assertInertia(fn (Assert $page) => $page
                    ->has('bounties.data', 1)
                    ->where('bounties.data.0.title', 'UniqueTitleSearch')
                    ->where('filters.search', 'UniqueTitleSearch')
                );

            $this->actingAs($this->user)
                ->get('/dashboard?search=HiddenDescSearch')
                ->assertInertia(fn (Assert $page) => $page
                    ->has('bounties.data', 1)
                    ->where('bounties.data.0.title', 'Other Task')
                );

        } catch (\Throwable $e) {
            echo "Message: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";

            throw $e;
        }
    }

    public function test_works_with_multiple_filters_together()
    {
        $creator = User::factory()->create();
        $repo = Repo::factory()->create(['user_id' => $creator->id]);

        $issue1 = Issue::factory()->create(['repo_id' => $repo->id]);
        Bounty::factory()->create(['issue_id' => $issue1->id, 'title' => 'Target API', 'status' => 'open', 'languages' => ['PHP'], 'reward_xp' => 100]);

        $issue2 = Issue::factory()->create(['repo_id' => $repo->id]);
        Bounty::factory()->create(['issue_id' => $issue2->id, 'title' => 'Wrong Lang API', 'status' => 'open', 'languages' => ['JavaScript'], 'reward_xp' => 100]);

        $this->actingAs($this->user)
            ->get('/dashboard?language=PHP&search=API')
            ->assertInertia(fn (Assert $page) => $page
                ->has('bounties.data', 1)
                ->where('bounties.data.0.title', 'Target API')
            );
    }

    public function test_displays_top_5_popular_bounties_sorted_by_submission_count()
    {
        $creator = User::factory()->create();
        $repo = Repo::factory()->create(['user_id' => $creator->id]);

        for ($i = 1; $i <= 6; $i++) {
            $issue = Issue::factory()->create(['repo_id' => $repo->id]);
            $bounty = Bounty::factory()->create([
                'issue_id' => $issue->id,
                'title' => "Bounty Rank $i",
                'status' => 'open',
                'languages' => ['PHP'],
                'reward_xp' => 100
            ]);
            Submission::factory()->count($i * 10)->create(['bounty_id' => $bounty->id]);
        }

        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->has('popularBounties', 6)
                ->where('popularBounties.0.title', 'Bounty Rank 6')
                ->where('popularBounties.4.title', 'Bounty Rank 2')
            );
    }

    public function test_popular_bounties_are_clickable_links()
    {
        $creator = User::factory()->create();
        $repo = Repo::factory()->create(['user_id' => $creator->id]);

        $issue = Issue::factory()->create(['repo_id' => $repo->id]);
        $bounty = Bounty::factory()->create(['issue_id' => $issue->id, 'title' => 'Top Hit', 'status' => 'open', 'languages' => ['PHP'], 'reward_xp' => 100]);
        Submission::factory()->count(50)->create(['bounty_id' => $bounty->id]);

        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertSee('Top Hit');
    }
}

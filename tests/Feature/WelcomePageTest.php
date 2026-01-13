<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bounty;
use App\Models\Issue;
use App\Models\Repo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    use DatabaseTruncation;

    public function test_welcome_page_shows_login_and_register_buttons()
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('auth.user', null)
        );
    }

    public function test_welcome_page_displays_bounty_list()
    {
        $creator = User::factory()->create();
        $repo = Repo::factory()->create(['user_id' => $creator->id]);
        $issue = Issue::factory()->create(['repo_id' => $repo->id]);

        Bounty::factory()->create([
            'issue_id' => $issue->id,
            'title' => 'Public Welcome Task',
            'status' => 'open',
            'languages' => ['PHP'],
            'reward_xp' => 100
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->has('bounties.data', 1)
            ->where('bounties.data.0.title', 'Public Welcome Task')
        );
    }
}

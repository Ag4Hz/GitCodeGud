<?php

namespace Tests\Feature\BountyManagement;

use App\Models\User;
use App\Models\Bounty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BountyDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_bounty_list_renders_correctly(): void
    {
        $user = User::factory()->create();

        $bounty = Bounty::factory()->create([
            'title' => 'Test Bounty',
        ]);

        $this->actingAs($user)
            ->get(route('bounties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('bounties.data.0.title', 'Test Bounty')
            );
    }

    public function test_bounty_detail_page_shows_all_required_fields(): void
    {
        $user = User::factory()->create();

        $bounty = Bounty::factory()->create([
            'title' => 'Test Bounty',
            'description' => 'Test Description',
        ]);

        $this->actingAs($user)
            ->get(route('bounties.show', $bounty))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('bounty.title', 'Test Bounty')
                ->where('bounty.description', 'Test Description')
            );
    }

    public function test_filters_can_be_cleared()
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('This test requires PostgreSQL');
        }
        /** @var User $user */
        $user = User::factory()->create();

        Bounty::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('bounties.index', ['search' => 'test']))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('bounties.index'))
            ->assertOk();
    }
}

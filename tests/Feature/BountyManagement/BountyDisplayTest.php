<?php

namespace Tests\Feature\BountyManagement;

use App\Models\Bounty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BountyDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_bounty_list_renders_correctly(): void
    {
        $user = User::factory()->create();

        Bounty::factory()->create([
            'title' => 'Test Bounty',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->get(route('bounties.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('bounties')
                ->has('bounties.data')
                ->has('bounties.data.0', fn (Assert $bounty) => $bounty
                    ->where('title', 'Test Bounty')
                    ->etc()
                )
            );
    }

    public function test_bounty_detail_page_shows_all_required_fields(): void
    {
        $user = User::factory()->create();

        $bounty = Bounty::factory()->create([
            'title' => 'Test Bounty',
            'description' => 'Test Description',
            'status' => 'open',
        ]);

        $this->actingAs($user)
            ->get(route('bounties.show', $bounty))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('bounty', fn (Assert $bounty) => $bounty
                    ->where('title', 'Test Bounty')
                    ->where('description', 'Test Description')
                    ->etc()
                )
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

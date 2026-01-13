<?php

namespace Tests\Feature\BountyManagement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BountyAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_unauthenticated_user_to_login_when_creating_bounty()
    {
        $this->get(route('bounties.create'))
            ->assertRedirect(route('login'));
    }

    public function test_redirects_unauthenticated_user_when_viewing_bounties()
    {
        $this->get(route('bounties.index'))
            ->assertRedirect(route('login'));
    }
}

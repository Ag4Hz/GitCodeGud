<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountsSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_connected_oauth_providers(): void
    {
        $user = User::factory()->create();

        UserProvider::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '123456',
            'nickname' => 'githubuser',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        UserProvider::create([
            'user_id' => $user->id,
            'provider' => 'gitlab',
            'provider_id' => '789012',
            'nickname' => 'gitlabuser',
            'avatar' => 'https://example.com/avatar2.jpg',
        ]);

        $response = $this->actingAs($user)->get('/settings/accounts');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/Accounts')
            ->has('linkedProviders', 2)
            ->where('linkedProviders.0.provider', 'github')
            ->where('linkedProviders.1.provider', 'gitlab')
        );
    }

    public function test_user_can_view_accounts_page_with_no_providers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/settings/accounts');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/Accounts')
            ->has('linkedProviders', 0)
        );
    }

    public function test_user_can_disconnect_oauth_provider_when_multiple_exist(): void
    {
        $user = User::factory()->create();

        UserProvider::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '123456',
            'nickname' => 'githubuser',
        ]);

        UserProvider::create([
            'user_id' => $user->id,
            'provider' => 'gitlab',
            'provider_id' => '789012',
            'nickname' => 'gitlabuser',
        ]);

        $response = $this->actingAs($user)->delete('/settings/accounts/github');

        $response->assertRedirect('/settings/accounts');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('user_providers', [
            'user_id' => $user->id,
            'provider' => 'github',
        ]);

        $this->assertDatabaseHas('user_providers', [
            'user_id' => $user->id,
            'provider' => 'gitlab',
        ]);
    }

    public function test_user_cannot_disconnect_last_oauth_provider(): void
    {
        $user = User::factory()->create();

        UserProvider::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '123456',
            'nickname' => 'githubuser',
        ]);

        $response = $this->actingAs($user)->delete('/settings/accounts/github');

        $response->assertRedirect('/settings/accounts');
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('user_providers', [
            'user_id' => $user->id,
            'provider' => 'github',
        ]);
    }

    public function test_accounts_page_shows_can_disconnect_status(): void
    {
        $user = User::factory()->create();

        // Single provider
        UserProvider::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '123456',
            'nickname' => 'githubuser',
        ]);

        $response = $this->actingAs($user)->get('/settings/accounts');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/Accounts')
            ->where('canDisconnect', false)
        );

        UserProvider::create([
            'user_id' => $user->id,
            'provider' => 'gitlab',
            'provider_id' => '789012',
            'nickname' => 'gitlabuser',
        ]);

        $response = $this->actingAs($user)->get('/settings/accounts');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('settings/Accounts')
            ->where('canDisconnect', true)
        );
    }

    public function test_guest_cannot_access_accounts_settings(): void
    {
        $response = $this->get('/settings/accounts');

        $response->assertRedirect('/login');
    }
}


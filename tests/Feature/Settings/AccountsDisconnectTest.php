<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountsDisconnectTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_page_renders_correctly(): void
    {
        $response = $this->get(route('password.request'));
        $response->assertOk();
    }

    public function test_accounts_page_requires_auth_and_renders_for_authenticated_user(): void
    {
        $this->get(route('accounts.edit'))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('accounts.edit'))
            ->assertOk();
    }

    public function test_cannot_disconnect_last_linked_provider(): void
    {
        $user = User::factory()->create();

        UserProvider::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '1',
            'provider_username' => 'u',
            'provider_email' => $user->email,
            'nickname' => 'u',
            'avatar' => null,
            'token' => 't',
            'refresh_token' => 'r',
        ]);

        $response = $this->actingAs($user)
            ->delete(route('accounts.disconnect', ['provider' => 'github']));

        $response->assertRedirect(route('accounts.edit'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('user_providers', [
            'user_id' => $user->id,
            'provider' => 'github',
        ]);
    }

    public function test_can_disconnect_provider_when_multiple_linked(): void
    {
        $user = User::factory()->create();

        UserProvider::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '1',
            'provider_username' => 'gh',
            'provider_email' => $user->email,
            'nickname' => 'gh',
            'avatar' => null,
            'token' => 't',
            'refresh_token' => 'r',
        ]);

        UserProvider::create([
            'user_id' => $user->id,
            'provider' => 'gitlab',
            'provider_id' => '2',
            'provider_username' => 'gl',
            'provider_email' => $user->email,
            'nickname' => 'gl',
            'avatar' => null,
            'token' => 't',
            'refresh_token' => 'r',
        ]);

        $response = $this->actingAs($user)
            ->delete(route('accounts.disconnect', ['provider' => 'github']));

        $response->assertRedirect(route('accounts.edit'));
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

    public function test_disconnect_only_affects_authenticated_user_records(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        UserProvider::create([
            'user_id' => $userA->id,
            'provider' => 'github',
            'provider_id' => 'a1',
            'provider_username' => 'a',
            'provider_email' => $userA->email,
            'nickname' => 'a',
            'avatar' => null,
            'token' => 't',
            'refresh_token' => 'r',
        ]);

        UserProvider::create([
            'user_id' => $userA->id,
            'provider' => 'gitlab',
            'provider_id' => 'a2',
            'provider_username' => 'a',
            'provider_email' => $userA->email,
            'nickname' => 'a',
            'avatar' => null,
            'token' => 't',
            'refresh_token' => 'r',
        ]);

        UserProvider::create([
            'user_id' => $userB->id,
            'provider' => 'github',
            'provider_id' => 'b1',
            'provider_username' => 'b',
            'provider_email' => $userB->email,
            'nickname' => 'b',
            'avatar' => null,
            'token' => 't',
            'refresh_token' => 'r',
        ]);

        $this->actingAs($userA)
            ->delete(route('accounts.disconnect', ['provider' => 'github']))
            ->assertRedirect(route('accounts.edit'));

        $this->assertDatabaseMissing('user_providers', [
            'user_id' => $userA->id,
            'provider' => 'github',
        ]);

        $this->assertDatabaseHas('user_providers', [
            'user_id' => $userB->id,
            'provider' => 'github',
        ]);
    }
}


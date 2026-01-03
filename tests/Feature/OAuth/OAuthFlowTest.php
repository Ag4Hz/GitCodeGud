<?php

use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\Support\FakeSocialiteUser;

function socialiteDriverMockForRedirect(string $provider, array $expectedScopes): void
{
    $driver = Mockery::mock();

    Socialite::shouldReceive('driver')
        ->once()
        ->with($provider)
        ->andReturn($driver);

    $driver->shouldReceive('scopes')
        ->once()
        ->with($expectedScopes)
        ->andReturnSelf();

    $driver->shouldReceive('redirect')
        ->once()
        ->andReturn(redirect('https://example.com/oauth/' . $provider));
}

function socialiteDriverMockForCallback(string $provider, FakeSocialiteUser $providerUser): void
{
    $driver = Mockery::mock();

    Socialite::shouldReceive('driver')
        ->once()
        ->with($provider)
        ->andReturn($driver);

    $driver->shouldReceive('user')
        ->once()
        ->andReturn($providerUser);
}

describe('OAuth redirect', function () {
    it('user can login with GitHub (redirect endpoint returns redirect)', function () {
        socialiteDriverMockForRedirect('github', ['read:user', 'user:email']);

        $this->get(route('oauth.redirect', ['provider' => 'github'], absolute: false))
            ->assertRedirect('https://example.com/oauth/github');
    });

    it('user can login with GitLab (redirect endpoint returns redirect)', function () {
        socialiteDriverMockForRedirect('gitlab', ['read_user', 'read_api']);

        $this->get(route('oauth.redirect', ['provider' => 'gitlab'], absolute: false))
            ->assertRedirect('https://example.com/oauth/gitlab');
    });

    it('user can login with Bitbucket (redirect endpoint returns redirect)', function () {
        socialiteDriverMockForRedirect('bitbucket', ['account', 'repository']);

        $this->get(route('oauth.redirect', ['provider' => 'bitbucket'], absolute: false))
            ->assertRedirect('https://example.com/oauth/bitbucket');
    });
});

describe('OAuth callback (login/registration flow)', function () {
    it('OAuth creates new user if email does not exist', function () {
        $provider = 'github';

        $providerUser = new FakeSocialiteUser(
            id: 'p-1',
            email: 'new-oauth@example.com',
            name: 'New OAuth',
            nickname: 'newoauth',
            avatar: 'https://example.com/avatar.png',
            token: 'token-a',
            refreshToken: 'refresh-a',
        );

        socialiteDriverMockForCallback($provider, $providerUser);

        $response = $this->get(route('oauth.callback', ['provider' => $provider], absolute: false) . '?code=abc');

        $response->assertRedirect('/dashboard');

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'new-oauth@example.com',
        ]);

        $user = User::whereEmail('new-oauth@example.com')->firstOrFail();

        $this->assertDatabaseHas('user_providers', [
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => 'p-1',
            'provider_email' => 'new-oauth@example.com',
            'token' => 'token-a',
            'refresh_token' => 'refresh-a',
        ]);
    });

    it('OAuth links to existing user by email match', function () {
        $provider = 'gitlab';

        $existing = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing User',
        ]);

        $providerUser = new FakeSocialiteUser(
            id: 'p-2',
            email: 'existing@example.com',
            name: 'GitLab Name',
            nickname: 'gitlabNick',
            avatar: null,
            token: 'token-b',
            refreshToken: null,
        );

        socialiteDriverMockForCallback($provider, $providerUser);

        $this->get(route('oauth.callback', ['provider' => $provider], absolute: false) . '?code=abc')
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($existing);

        $this->assertDatabaseHas('user_providers', [
            'user_id' => $existing->id,
            'provider' => $provider,
            'provider_id' => 'p-2',
            'provider_email' => 'existing@example.com',
            'token' => 'token-b',
            'refresh_token' => null,
        ]);
    });

    it('OAuth updates provider tokens on re-authentication', function () {
        $provider = 'bitbucket';

        $user = User::factory()->create(['email' => 'reauth@example.com']);

        $existingProvider = UserProvider::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => 'p-3',
            'provider_username' => 'old',
            'provider_email' => $user->email,
            'nickname' => 'old',
            'avatar' => null,
            'token' => 'old-token',
            'refresh_token' => 'old-refresh',
        ]);

        $providerUser = new FakeSocialiteUser(
            id: 'p-3',
            email: $user->email,
            name: 'BB Name',
            nickname: 'newNick',
            avatar: 'https://example.com/new.png',
            token: 'new-token',
            refreshToken: 'new-refresh',
        );

        socialiteDriverMockForCallback($provider, $providerUser);

        $this->get(route('oauth.callback', ['provider' => $provider], absolute: false) . '?code=abc')
            ->assertRedirect('/dashboard');

        $existingProvider->refresh();

        expect($existingProvider->token)->toBe('new-token');
        expect($existingProvider->refresh_token)->toBe('new-refresh');
        expect($existingProvider->nickname)->toBe('newNick');
        expect($existingProvider->avatar)->toBe('https://example.com/new.png');
    });
});

describe('OAuth callback (linking additional account while authenticated)', function () {
    it('blocks linking a provider account already linked to another user', function () {
        $provider = 'github';

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        UserProvider::create([
            'user_id' => $userB->id,
            'provider' => $provider,
            'provider_id' => 'p-4',
            'provider_username' => 'already',
            'provider_email' => 'already@example.com',
            'nickname' => 'already',
            'avatar' => null,
            'token' => 't',
            'refresh_token' => null,
        ]);

        $providerUser = new FakeSocialiteUser(
            id: 'p-4',
            email: 'already@example.com',
            name: 'Already',
            nickname: 'already',
            avatar: null,
            token: 'token-x',
            refreshToken: null,
        );

        socialiteDriverMockForCallback($provider, $providerUser);

        $this->actingAs($userA)
            ->get(route('oauth.callback', ['provider' => $provider], absolute: false) . '?code=abc')
            ->assertRedirect(route('accounts.edit', absolute: false))
            ->assertSessionHasErrors('provider');
    });

    it('links provider to current user by email match', function () {
        $provider = 'gitlab';

        $user = User::factory()->create();

        $providerUser = new FakeSocialiteUser(
            id: 'p-5',
            email: $user->email,
            name: 'GL',
            nickname: 'gl',
            avatar: null,
            token: 'token-y',
            refreshToken: null,
        );

        socialiteDriverMockForCallback($provider, $providerUser);

        $this->actingAs($user)
            ->get(route('oauth.callback', ['provider' => $provider], absolute: false) . '?code=abc')
            ->assertRedirect(route('accounts.edit', absolute: false))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('user_providers', [
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => 'p-5',
        ]);
    });
});


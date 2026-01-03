<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\Support\FakeSocialiteUser;
use Tests\TestCase;

class OAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public static function providerMatrix(): array
    {
        return [
            'github' => ['github'],
            'gitlab' => ['gitlab'],
            'bitbucket' => ['bitbucket'],
        ];
    }

    #[DataProvider('providerMatrix')]
    public function test_user_can_login_with_oauth_provider_creating_new_user_if_email_does_not_exist(string $provider): void
    {
        $fake = new FakeSocialiteUser(
            id: 'prov-1',
            email: 'new-user@example.com',
            name: 'New OAuth User',
            nickname: 'new-oauth',
            avatar: 'https://example.com/a.png',
            token: 'token-a',
            refreshToken: 'refresh-a',
        );

        $this->mockSocialiteUser($provider, $fake);

        $response = $this->get(route('oauth.callback', ['provider' => $provider]) . '?code=abc');

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'email' => 'new-user@example.com',
            'name' => 'New OAuth User',
        ]);

        $user = User::where('email', 'new-user@example.com')->firstOrFail();

        $this->assertDatabaseHas('user_providers', [
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => 'prov-1',
            'token' => 'token-a',
            'refresh_token' => 'refresh-a',
        ]);
    }

    #[DataProvider('providerMatrix')]
    public function test_oauth_links_to_existing_user_by_email_match(string $provider): void
    {
        $existing = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Existing User',
        ]);

        $fake = new FakeSocialiteUser(
            id: 'prov-2',
            email: 'existing@example.com',
            name: 'Provider Name',
            nickname: 'existing-oauth',
            avatar: null,
            token: 'token-b',
            refreshToken: 'refresh-b',
        );

        $this->mockSocialiteUser($provider, $fake);

        $response = $this->get(route('oauth.callback', ['provider' => $provider]) . '?code=abc');

        $this->assertAuthenticatedAs($existing);
        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('user_providers', [
            'user_id' => $existing->id,
            'provider' => $provider,
            'provider_id' => 'prov-2',
        ]);
    }

    #[DataProvider('providerMatrix')]
    public function test_oauth_updates_provider_tokens_on_reauthentication(string $provider): void
    {
        $user = User::factory()->create([
            'email' => 'reauth@example.com',
        ]);

        $providerRow = UserProvider::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => 'prov-3',
            'provider_username' => 'old',
            'provider_email' => 'reauth@example.com',
            'nickname' => 'old',
            'avatar' => null,
            'token' => 'old-token',
            'refresh_token' => 'old-refresh',
        ]);

        $fake = new FakeSocialiteUser(
            id: 'prov-3',
            email: 'reauth@example.com',
            name: 'Reauth Name',
            nickname: 'reauth-nick',
            avatar: 'https://example.com/new.png',
            token: 'new-token',
            refreshToken: 'new-refresh',
        );

        $this->mockSocialiteUser($provider, $fake);

        $response = $this->get(route('oauth.callback', ['provider' => $provider]) . '?code=abc');

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');

        $providerRow->refresh();
        $this->assertSame('new-token', $providerRow->token);
        $this->assertSame('new-refresh', $providerRow->refresh_token);
        $this->assertSame('reauth-nick', $providerRow->nickname);
        $this->assertSame('https://example.com/new.png', $providerRow->avatar);
    }

    #[DataProvider('providerMatrix')]
    public function test_oauth_redirect_endpoint_redirects_to_provider(string $provider): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('scopes')->once()->andReturnSelf();
        $driver->shouldReceive('redirect')->once()->andReturn(new RedirectResponse('https://oauth.example.com'));

        Socialite::shouldReceive('driver')->once()->with($provider)->andReturn($driver);

        $response = $this->get(route('oauth.redirect', ['provider' => $provider]));
        $response->assertRedirect();
    }

    public function test_oauth_redirect_rejects_invalid_provider(): void
    {
        $response = $this->get('/auth/not-a-provider/redirect');

        // Route constraint should 404.
        $response->assertStatus(404);
    }

    #[DataProvider('providerMatrix')]
    public function test_oauth_callback_requires_authorization_code(string $provider): void
    {
        $response = $this->get(route('oauth.callback', ['provider' => $provider]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('provider');
        $this->assertGuest();
    }

    #[DataProvider('providerMatrix')]
    public function test_oauth_callback_error_query_redirects_back_to_login(string $provider): void
    {
        $response = $this->get(route('oauth.callback', ['provider' => $provider]) . '?error=access_denied');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('provider');
        $this->assertGuest();
    }

    #[DataProvider('providerMatrix')]
    public function test_authenticated_user_can_link_provider_and_is_redirected_to_accounts_page(string $provider): void
    {
        $user = User::factory()->create();

        $fake = new FakeSocialiteUser(
            id: 'prov-link-1',
            email: 'link@example.com',
            name: 'Link Name',
            nickname: 'link-nick',
            avatar: null,
            token: 't',
            refreshToken: 'r',
        );

        $this->mockSocialiteUser($provider, $fake);

        $response = $this->actingAs($user)
            ->get(route('oauth.callback', ['provider' => $provider]) . '?code=abc');

        $response->assertRedirect(route('accounts.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_providers', [
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => 'prov-link-1',
        ]);
    }

    #[DataProvider('providerMatrix')]
    public function test_authenticated_user_cannot_link_provider_already_linked_to_another_user(string $provider): void
    {
        $owner = User::factory()->create();
        UserProvider::create([
            'user_id' => $owner->id,
            'provider' => $provider,
            'provider_id' => 'prov-owned',
            'provider_username' => 'owned',
            'provider_email' => 'owned@example.com',
            'nickname' => 'owned',
            'avatar' => null,
            'token' => 't1',
            'refresh_token' => 'r1',
        ]);

        $other = User::factory()->create();

        $fake = new FakeSocialiteUser(
            id: 'prov-owned',
            email: 'owned@example.com',
            name: 'Owned Name',
            nickname: 'owned',
            avatar: null,
            token: 't2',
            refreshToken: 'r2',
        );

        $this->mockSocialiteUser($provider, $fake);

        $response = $this->actingAs($other)
            ->get(route('oauth.callback', ['provider' => $provider]) . '?code=abc');

        $response->assertRedirect(route('accounts.edit'));
        $response->assertSessionHasErrors('provider');

        $this->assertDatabaseMissing('user_providers', [
            'user_id' => $other->id,
            'provider' => $provider,
            'provider_id' => 'prov-owned',
        ]);
    }

    private function mockSocialiteUser(string $provider, FakeSocialiteUser $fake): void
    {
        $driver = Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($fake);

        Socialite::shouldReceive('driver')->once()->with($provider)->andReturn($driver);
    }
}

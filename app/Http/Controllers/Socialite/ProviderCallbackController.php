<?php

namespace App\Http\Controllers\Socialite;

use App\Helpers\XPHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class ProviderCallbackController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $provider)
    {
        // Check if user is already authenticated (linking additional account)
        $isLinking         = Auth::check();
        $authenticatedUser = Auth::user();

        if ($provider === 'jira' && !$isLinking) {
            return redirect(route('login'))->withErrors([
                'provider' => 'Please log in first, then connect your Jira account from Account Settings.',
            ]);
        }

        if ($request->has('error')) {
            $redirectRoute = $isLinking ? route('accounts.edit') : route('login');
            return redirect($redirectRoute)->withErrors([
                'provider' => "Authorization failed: " . $request->get('error_description', $request->get('error'))
            ]);
        }

        if (!$request->has('code')) {
            $redirectRoute = $isLinking ? route('accounts.edit') : route('login');
            return redirect($redirectRoute)->withErrors([
                'provider' => "No authorization code received from {$provider}. Please try again."
            ]);
        }

        $driverName = $provider === 'jira' ? 'atlassian' : $provider;
        $driver = Socialite::driver($driverName);

        if (in_array($provider, ['gitlab', 'bitbucket'])) {
            $driver = $driver->stateless();
        }

        $providerUser = $driver->user();

        $providerId = (string) $providerUser->getId();
        if ($provider === 'jira') {
            $cloudId = $this->resolveJiraCloudId($providerUser->token);
            if ($cloudId) {
                $providerId = $cloudId;
            }
        }

        $userProvider = UserProvider::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($isLinking) {
            // User is authenticated and linking an additional account
            if ($userProvider && $userProvider->user_id !== $authenticatedUser->id) {
                return redirect(route('accounts.edit'))->withErrors([
                    'provider' => "This {$provider} account is already linked to another user."
                ]);
            }

            if (!$userProvider) {
                UserProvider::create([
                    'user_id'           => $authenticatedUser->id,
                    'provider'          => $provider,
                    'provider_id'       => $providerId,
                    'provider_username' => $this->getNickname($providerUser, $provider),
                    'provider_email'    => $providerUser->getEmail(),
                    'nickname'          => $this->getNickname($providerUser, $provider),
                    'avatar'            => $providerUser->getAvatar(),
                    'token'             => $providerUser->token,
                    'refresh_token'     => $providerUser->refreshToken ?? null,
                ]);
            } else {
                $userProvider->update([
                    'provider_username' => $this->getNickname($providerUser, $provider),
                    'provider_email'    => $providerUser->getEmail(),
                    'nickname'          => $this->getNickname($providerUser, $provider),
                    'avatar'            => $providerUser->getAvatar(),
                    'token'             => $providerUser->token,
                    'refresh_token'     => $providerUser->refreshToken ?? null,
                ]);
            }

            return redirect(route('accounts.edit'))
                ->with('success', ucfirst($provider) . ' account connected successfully!');
        }

        if (!$userProvider) {
            $user = User::firstOrCreate(
                ['email' => $providerUser->getEmail()],
                [
                    'name'     => $providerUser->getName(),
                    'nickname' => $this->getNickname($providerUser, $provider),
                ]
            );
            if ($user->wasRecentlyCreated) {
                XPHelper::grantStarterXP($user);
            }


            UserProvider::create([
                'user_id'           => $user->id,
                'provider'          => $provider,
                'provider_id'       => $providerId,
                'provider_username' => $this->getNickname($providerUser, $provider),
                'provider_email'    => $providerUser->getEmail(),
                'nickname'          => $this->getNickname($providerUser, $provider),
                'avatar'            => $providerUser->getAvatar(),
                'token'             => $providerUser->token,
                'refresh_token'     => $providerUser->refreshToken ?? null,
            ]);
        } else {
            $user = $userProvider->user;

            if (!$user) {
                $user = User::firstOrCreate(
                    ['email' => $providerUser->getEmail()],
                    [
                        'name'     => $providerUser->getName(),
                        'nickname' => $this->getNickname($providerUser, $provider),
                    ]
                );
                if ($user->wasRecentlyCreated) {
                    XPHelper::grantStarterXP($user);
                }
                $userProvider->update(['user_id' => $user->id]);
            }

            $userProvider->update([
                'provider_username' => $this->getNickname($providerUser, $provider),
                'provider_email'    => $providerUser->getEmail(),
                'nickname'          => $this->getNickname($providerUser, $provider),
                'avatar'            => $providerUser->getAvatar(),
                'token'             => $providerUser->token,
                'refresh_token'     => $providerUser->refreshToken ?? null,
            ]);
        }

        Auth::login($user);
        return redirect('/dashboard');
    }

    private function resolveJiraCloudId(string $token): ?string
    {
        $response = Http::withToken($token)
            ->acceptJson()
            ->get('https://api.atlassian.com/oauth/token/accessible-resources');

        if ($response->failed()) {
            return null;
        }

        $sites = $response->json();
        if (empty($sites) || !is_array($sites)) {
            return null;
        }

        return $sites[0]['id'] ?? null;
    }

    private function getNickname($providerUser, string $provider): string
    {
        if (in_array($provider, ['gitlab', 'bitbucket', 'jira']) && method_exists($providerUser, 'getNickname')) {
            $nick = $providerUser->getNickname();
            if ($nick) {
                return $nick;
            }
        }
        return $providerUser->getName() ?? $providerUser->getEmail() ?? 'Unknown';
    }
}

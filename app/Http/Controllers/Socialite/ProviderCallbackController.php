<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class ProviderCallbackController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $provider)
    {
        // Check if user is already authenticated (linking additional account)
        $isLinking = Auth::check();
        $authenticatedUser = Auth::user();

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

        $providerUser = Socialite::driver($provider)->user();

        $userProvider = UserProvider::where('provider', $provider)
            ->where('provider_id', (string)$providerUser->getId())
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
                    'user_id' => $authenticatedUser->id,
                    'provider' => $provider,
                    'provider_id' => (string)$providerUser->getId(),
                    'provider_username' => $this->getNickname($providerUser, $provider),
                    'provider_email' => $providerUser->getEmail(),
                    'nickname' => $this->getNickname($providerUser, $provider),
                    'avatar' => $providerUser->getAvatar(),
                    'token' => $providerUser->token,
                    'refresh_token' => $providerUser->refreshToken ?? null,
                ]);
            } else {
                $userProvider->update([
                    'provider_username' => $this->getNickname($providerUser, $provider),
                    'provider_email' => $providerUser->getEmail(),
                    'nickname' => $this->getNickname($providerUser, $provider),
                    'avatar' => $providerUser->getAvatar(),
                    'token' => $providerUser->token,
                    'refresh_token' => $providerUser->refreshToken ?? null,
                ]);
            }

            return redirect(route('accounts.edit'))->with('success', ucfirst($provider) . ' account connected successfully!');
        }

        // User is not authenticated - this is a login/registration flow
        if (!$userProvider) {
            $user = User::firstOrCreate(
                ['email' => $providerUser->getEmail()],
                [
                    'name' => $providerUser->getName(),
                    'nickname' => $this->getNickname($providerUser, $provider),
                ]
            );

            UserProvider::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => (string)$providerUser->getId(),
                'provider_username' => $this->getNickname($providerUser, $provider),
                'provider_email' => $providerUser->getEmail(),
                'nickname' => $this->getNickname($providerUser, $provider),
                'avatar' => $providerUser->getAvatar(),
                'token' => $providerUser->token,
                'refresh_token' => $providerUser->refreshToken ?? null,
            ]);
        } else {
            $user = $userProvider->user;

            if (!$user) {
                $user = User::firstOrCreate(
                    ['email' => $providerUser->getEmail()],
                    [
                        'name' => $providerUser->getName(),
                        'nickname' => $this->getNickname($providerUser, $provider),
                    ]
                );

                $userProvider->update(['user_id' => $user->id]);
            }

            $userProvider->update([
                'provider_username' => $this->getNickname($providerUser, $provider),
                'provider_email' => $providerUser->getEmail(),
                'nickname' => $this->getNickname($providerUser, $provider),
                'avatar' => $providerUser->getAvatar(),
                'token' => $providerUser->token,
                'refresh_token' => $providerUser->refreshToken ?? null,
            ]);
        }

        Auth::login($user);

        return redirect('/dashboard');
    }

    private function getNickname($providerUser, string $provider): string
    {
        if (in_array($provider, ['gitlab', 'bitbucket']) && method_exists($providerUser, 'getNickname')) {
            return $providerUser->getNickname();
        }

        return $providerUser->getNickname() ?? $providerUser->getName();
    }
}

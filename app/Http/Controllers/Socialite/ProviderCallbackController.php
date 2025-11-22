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
        if ($request->has('error')) {
            return redirect(route('login'))->withErrors([
                'provider' => "Authorization failed: " . $request->get('error_description', $request->get('error'))
            ]);
        }

        if (!$request->has('code')) {
            return redirect(route('login'))->withErrors([
                'provider' => "No authorization code received from {$provider}. Please try again."
            ]);
        }

        if ($provider === 'gitlab' || $provider === 'bitbucket') {
            $providerUser = Socialite::driver($provider)->stateless()->user();
        } else {
            $providerUser = Socialite::driver($provider)->user();
        }

        $userProvider = UserProvider::where('provider', $provider)
            ->where('provider_id', (string)$providerUser->getId())
            ->first();

        if (!$userProvider) {
            $user = User::firstOrCreate(
                ['email' => $providerUser->getEmail()],
                [
                    'name' => $providerUser->getName(),
                    'nickname' => $this->getNickname($providerUser, $provider),
                ]
            );

            $userProvider = UserProvider::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => (string)$providerUser->getId(),
                'provider_username' => $this->getNickname($providerUser, $provider),
                'provider_email' => $providerUser->getEmail(),
                'token' => $providerUser->token,
                'refresh_token' => $providerUser->refreshToken ?? null,
            ]);
        } else {
            $user = $userProvider->user;
            $userProvider->update([
                'token' => $providerUser->token,
                'refresh_token' => $providerUser->refreshToken ?? null,
            ]);
        }

        Auth::login($user);

        return redirect('/dashboard');
    }

    private function getNickname($providerUser, string $provider): string
    {
        if ($provider === 'gitlab' && method_exists($providerUser, 'getNickname')) {
            return $providerUser->getNickname();
        }

        if ($provider === 'bitbucket' && method_exists($providerUser, 'getNickname')) {
            return $providerUser->getNickname();
        }

        return $providerUser->getNickname() ?? $providerUser->getName();
    }
}

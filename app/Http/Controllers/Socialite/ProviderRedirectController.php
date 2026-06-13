<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class ProviderRedirectController extends Controller
{
    public function __invoke(Request $request, string $provider)
    {
        $validProviders = ['github', 'gitlab', 'bitbucket', 'jira'];
        if (!in_array($provider, $validProviders)) {
            return redirect(route('login'))->withErrors(['provider' => 'Invalid provider.']);
        }

        $scopes = match($provider) {
            'github'    => ['read:user', 'user:email','repo'],
            'gitlab'    => ['read_user', 'api'],
            'bitbucket' => ['account', 'repository', 'pullrequest'],
            'jira'      => ['read:me', 'read:jira-work', 'offline_access'],
        };

        $driverName = $provider === 'jira' ? 'atlassian' : $provider;

        try {
            $driver = Socialite::driver($driverName)->scopes($scopes);

            if (in_array($provider, ['gitlab', 'bitbucket'])) {
                $driver = $driver->stateless();
            }

            return $driver->redirect();
        } catch (\Exception $e) {
            return redirect(route('login'))->withErrors(['provider' => "Unable to connect to {$provider}."]);
        }
    }
}

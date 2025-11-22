<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class ProviderRedirectController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $provider)
    {
        $validProviders = ['github', 'gitlab', 'bitbucket'];
        if (!in_array($provider, $validProviders)) {
            return redirect(route('login'))->withErrors(['provider' => 'Invalid provider.']);
        }

        $scopes = match($provider) {
            'github' => ['read:repo', 'read:issue'],
            'gitlab' => ['read_user', 'api'],
            'bitbucket' => [],
        };

        $driver = Socialite::driver($provider);
        if (!empty($scopes)) {
            $driver = $driver->scopes($scopes);
        }
        if ($provider === 'gitlab' || $provider === 'bitbucket') {
            $driver = $driver->stateless();
        }

        return $driver->redirect();
    }
}

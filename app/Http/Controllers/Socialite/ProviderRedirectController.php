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
            'bitbucket' => ['repository:read', 'user:email:read'],
        };

        try {
            $driver = Socialite::driver($provider)->scopes($scopes);
            if ($provider === 'gitlab') {
                $driver = $driver->stateless();
            }

            $redirectUrl = $driver->redirect()->getTargetUrl();
            return $driver->redirect();
        } catch (\Exception $e) {
            \Log::error("OAuth redirect error for {$provider}: " . $e->getMessage(), [
                'exception' => class_basename($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return redirect(route('login'))->withErrors(['provider' => "Unable to connect to {$provider}."]);
        }
    }
}

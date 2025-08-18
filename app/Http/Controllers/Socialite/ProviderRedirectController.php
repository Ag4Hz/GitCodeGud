<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class ProviderRedirectController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $provider = 'github';

        try {
            return Socialite::driver($provider)
                ->scopes([
                    'read:repo',
                    'read:issue'
                ])
                ->redirect();
        } catch (\Exception $e) {
            return redirect(route('login'))->withErrors(['provider' => 'Unable to connect to GitHub.']);
        }
    }
}

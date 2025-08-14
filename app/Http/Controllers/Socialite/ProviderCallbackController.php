<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class ProviderCallbackController extends Controller
{
    /**
     * Handle the incoming request.
     */

    public function __invoke()
    {
        $provider = 'github';
        $githubUser = Socialite::driver($provider)->user();
        $user = User::updateOrCreate([
            'oauth_provider_id' => $githubUser->id,
            'oauth_provider' => $provider,
        ], [
            'name' => $githubUser->name,
            'email' => $githubUser->email,
            'oauth_provider_token' => $githubUser->token,
        ]);

        Auth::login($user);

        return redirect('/dashboard');
    }
}

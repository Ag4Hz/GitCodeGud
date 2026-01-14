<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserProvider;
use Inertia\Inertia;

class AccountsController
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $providers = UserProvider::where('user_id', $user->id)
            ->get()
            ->map(fn($provider) => [
                'provider' => $provider->provider,
                'provider_id' => $provider->provider_id,
                'nickname' => $provider->nickname ?? 'Unknown',
                'avatar' => $provider->avatar ?? '',
            ]);

        return Inertia::render('settings/Accounts', [
            'linkedProviders' => $providers,
            'canDisconnect' => $providers->count() > 1,
        ]);
    }

    public function disconnect(Request $request, $provider)
    {
        $user = $request->user();

        $providersCount = UserProvider::where('user_id', $user->id)->count();

        if ($providersCount <= 1) {
            return redirect()->route('accounts.edit')->with('error', 'Cannot disconnect your last provider');
        }

        UserProvider::where('user_id', $user->id)
            ->where('provider', $provider)
            ->delete();

        return redirect()->route('accounts.edit')->with('success', ucfirst($provider) . ' account disconnected successfully');
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserProviderSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $providers = ['github', 'gitlab', 'bitbucket'];

        foreach ($users as $user) {
            $providerCount = rand(1, 3);
            $selectedProviders = array_rand(array_flip($providers), $providerCount);

            if (!is_array($selectedProviders)) {
                $selectedProviders = [$selectedProviders];
            }

            foreach ($selectedProviders as $provider) {
                $user->providers()->firstOrCreate(
                    [
                        'provider' => $provider,
                        'user_id' => $user->id,
                    ],
                    [
                        'provider_username' => $user->nickname ?? 'user' . $user->id,
                        'provider_id' => (string) rand(10000, 999999),
                    ]
                );
            }
        }
    }
}

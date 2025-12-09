<?php

namespace App\Services;

use App\Models\UserProvider;

class GitProviderFactory
{
    public static function getProvider(string $providerName, UserProvider $provider): GitProviderInterface
    {
        return match (strtolower($providerName)) {
            'github' => new GitHubApiService($provider),
            'gitlab' => new GitLabApiService($provider),
            'bitbucket' => new BitbucketApiService($provider),
            default => throw new \InvalidArgumentException("Unsupported git provider: {$providerName}"),
        };


    }
}

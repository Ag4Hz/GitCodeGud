<?php

namespace App\Services;

class GitProviderFactory
{
    public static function getProvider(string $providerName, $user): GitProviderInterface
    {
        return match (strtolower($providerName)) {
            'github' => new GitHubApiService($user),
            'gitlab' => new GitLabApiService($user),
            'bitbucket' => new BitbucketApiService($user),
            default => throw new \InvalidArgumentException("Unsupported git provider: {$providerName}"),
        };
    }
}

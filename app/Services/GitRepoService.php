<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Support\Facades\Log;
use Exception;

class GitRepoService
{
    private User $user;
    private array $providers = [];

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->initializeProviders();
    }

    private function initializeProviders(): void
    {
        $userProviders = $this->user->providers()->get();

        foreach ($userProviders as $userProvider) {
            $service = $this->createServiceForProvider($userProvider);
            if ($service && $service->hasValidToken()) {
                $this->providers[$userProvider->provider] = [
                    'service' => $service,
                    'provider' => $userProvider,
                ];
            }
        }
    }

    private function createServiceForProvider(UserProvider $userProvider): ?GitProviderInterface
    {
        return match ($userProvider->provider) {
            'github' => new GitHubApiService($this->user),
            'gitlab' => new GitLabApiService($userProvider),
            default => null,
        };
    }

    public function getConnectedProviders(): array
    {
        return array_keys($this->providers);
    }

    public function hasProvider(string $provider): bool
    {
        return isset($this->providers[$provider]);
    }

    public function getAllUserRepositories(array $params = []): array
    {
        $allRepositories = [];

        foreach ($this->providers as $providerName => $providerData) {
            try {
                $repos = $providerData['service']->getUserRepositories($params);
                $normalizedRepos = $this->normalizeRepositories($repos, $providerName);
                $allRepositories = array_merge($allRepositories, $normalizedRepos);
            } catch (Exception $e) {
                Log::warning('Failed to fetch repositories', [
                    'provider' => $providerName,
                    'message'  => $e->getMessage(),
                ]);
            }
        }

        usort($allRepositories, fn ($a, $b) =>
            ($b['updated_at'] ?? '') <=> ($a['updated_at'] ?? '')
        );

        return $allRepositories;
    }

    public function getRepositoryIssues(string $provider, string $repoFullName, array $params = []): array
    {
        if (!$this->hasProvider($provider)) {
            return [];
        }

        try {
            $issues = $this->providers[$provider]['service']->getRepositoryIssues($repoFullName, $params);
            return $this->normalizeIssues($issues, $provider);
        } catch (Exception $e) {
            Log::warning("Failed to fetch issues from {$provider}: " . $e->getMessage());
            return [];
        }
    }
    public function getRepositoryLanguages(string $provider, string $repoFullName): array
    {
        if (!$this->hasProvider($provider)) {
            return [];
        }

        try {
            return $this->providers[$provider]['service']->getRepositoryLanguages($repoFullName);
        } catch (Exception $e) {
            Log::warning("Failed to fetch languages from {$provider}: " . $e->getMessage());
            return [];
        }
    }

    public function canUserWriteToRepository(string $provider, string $repoFullName): bool
    {
        if (!$this->hasProvider($provider)) {
            return false;
        }

        try {
            return $this->providers[$provider]['service']->canUserWriteToRepository($repoFullName);
        } catch (Exception $e) {
            Log::warning("Failed to check write permission for {$provider}/{$repoFullName}: " . $e->getMessage());
            return false;
        }
    }

    public function isIssueOpen(string $provider, string $repoFullName, int $issueNumber): bool
    {
        if (!$this->hasProvider($provider)) {
            return false;
        }

        try {
            return $this->providers[$provider]['service']->isIssueOpen($repoFullName, $issueNumber);
        } catch (Exception $e) {
            Log::warning("Failed to check issue status for {$provider}/{$repoFullName}#{$issueNumber}: " . $e->getMessage());
            return false;
        }
    }

    public static function detectProviderFromUrl(string $url): ?string
    {
        if (GitHubApiService::isValidGitUrl($url) || GitHubApiService::isValidGitIssueUrl($url)) {
            return 'github';
        }
        if (GitLabApiService::isValidGitUrl($url) || GitLabApiService::isValidGitIssueUrl($url)) {
            return 'gitlab';
        }
        return null;
    }

    public static function parseGitUrl(string $url): ?array
    {
        $provider = self::detectProviderFromUrl($url);
        if (!$provider) {
            return null;
        }

        $result = match ($provider) {
            'github' => GitHubApiService::parseGitUrl($url),
            'gitlab' => GitLabApiService::parseGitUrl($url),
            default => null,
        };

        if ($result) {
            $result['provider'] = $provider;
        }

        return $result;
    }

    public static function parseGitIssueUrl(string $url): ?array
    {
        $provider = self::detectProviderFromUrl($url);
        if (!$provider) {
            return null;
        }

        $result = match ($provider) {
            'github' => GitHubApiService::parseGitIssueUrl($url),
            'gitlab' => GitLabApiService::parseGitIssueUrl($url),
            default => null,
        };

        if ($result) {
            $result['provider'] = $provider;
        }

        return $result;
    }

    private function normalizeRepositories(array $repos, string $provider): array
    {
        return array_map(function ($repo) use ($provider) {
            return match ($provider) {
                'github' => [
                    'id' => $repo['id'],
                    'name' => $repo['name'],
                    'full_name' => $repo['full_name'],
                    'description' => $repo['description'] ?? '',
                    'url' => $repo['html_url'],
                    'language' => $repo['language'] ?? 'Unknown',
                    'updated_at' => $repo['updated_at'],
                    'open_issues_count' => $repo['open_issues_count'] ?? 0,
                    'provider' => 'github',
                ],
                'gitlab' => [
                    'id' => $repo['id'],
                    'name' => $repo['name'] ?? $repo['path'],
                    'full_name' => $repo['path_with_namespace'],
                    'description' => $repo['description'] ?? '',
                    'url' => $repo['web_url'],
                    'language' => $repo['language'] ?? 'Unknown',
                    'updated_at' => $repo['last_activity_at'] ?? $repo['updated_at'] ?? null,
                    'open_issues_count' => $repo['open_issues_count'] ?? 0,
                    'provider' => 'gitlab',
                ],
                default => $repo,
            };
        }, $repos);
    }
    private function normalizeIssues(array $issues, string $provider): array
    {
        return array_map(function ($issue) use ($provider) {
            return match ($provider) {
                'github' => [
                    'id' => $issue['id'],
                    'number' => $issue['number'],
                    'title' => $issue['title'],
                    'body' => $issue['body'] ?? '',
                    'url' => $issue['html_url'],
                    'state' => $issue['state'],
                    'created_at' => $issue['created_at'],
                    'updated_at' => $issue['updated_at'],
                    'user' => [
                        'login' => $issue['user']['login'],
                        'avatar_url' => $issue['user']['avatar_url'],
                    ],
                    'labels' => array_map(fn($l) => ['name' => $l['name'], 'color' => $l['color']], $issue['labels'] ?? []),
                    'comments' => $issue['comments'] ?? 0,
                    'provider' => 'github',
                ],
                'gitlab' => [
                    'id' => $issue['id'],
                    'number' => $issue['iid'],
                    'title' => $issue['title'],
                    'body' => $issue['description'] ?? '',
                    'url' => $issue['web_url'],
                    'state' => $issue['state'] === 'opened' ? 'open' : $issue['state'],
                    'created_at' => $issue['created_at'],
                    'updated_at' => $issue['updated_at'],
                    'user' => [
                        'login' => $issue['author']['username'] ?? 'unknown',
                        'avatar_url' => $issue['author']['avatar_url'] ?? '',
                    ],
                    'labels' => array_map(fn($l) => ['name' => $l, 'color' => '6c757d'], $issue['labels'] ?? []),
                    'comments' => $issue['user_notes_count'] ?? 0,
                    'provider' => 'gitlab',
                ],
                default => $issue,
            };
        }, $issues);
    }
}


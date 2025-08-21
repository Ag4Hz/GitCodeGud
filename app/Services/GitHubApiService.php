<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GitHubApiService
{
    private const BASE_URL = 'https://api.github.com';
    private const USER_AGENT = 'GitCodeGud-App';
    private const API_VERSION = 'application/vnd.github.v3+json';

    public function __construct(
        private User $user
    ) {}

    private function createClient(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->user->oauth_provider_token,
            'Accept' => self::API_VERSION,
            'User-Agent' => self::USER_AGENT,
        ])->baseUrl(self::BASE_URL);
    }

    public function getUserRepositories(array $params = []): array
    {
        $defaultParams = [
            'type' => 'owner',
            'sort' => 'updated',
            'per_page' => 100
        ];
        $response = $this->createClient()->get('/user/repos', array_merge($defaultParams, $params));
        return $this->handleResponse($response, 'Failed to fetch GitHub repositories');
    }

    public function getRepositoryLanguages(string $repoFullName): array
    {
        $response = $this->createClient()->get("/repos/{$repoFullName}/languages");
        if ($response->failed()) {
            return [];
        }
        return $response->json() ?? [];
    }

    public function getRepository(string $repoFullName): array
    {
        $response = $this->createClient()->get("/repos/{$repoFullName}");
        return $this->handleResponse($response, "Failed to fetch repository: {$repoFullName}");
    }

    public function getUserProfile(): array
    {
        $response = $this->createClient()->get('/user');
        return $this->handleResponse($response, 'Failed to fetch GitHub user profile');
    }

    public function getRepositoryCommits(string $repoFullName, array $params = []): array
    {
        $defaultParams = [
            'author' => $this->user->oauth_provider_id ?? $this->user->email,
            'per_page' => 100
        ];

        $response = $this->createClient()
            ->get("/repos/{$repoFullName}/commits", array_merge($defaultParams, $params));
        if ($response->failed()) {
            return [];
        }
        return $response->json() ?? [];
    }

    /**
     * Get GitHub issue details including status
     */
    public function getIssue(string $repoFullName, int $issueNumber): array
    {
        $response = $this->createClient()->get("/repos/{$repoFullName}/issues/{$issueNumber}");
        return $this->handleResponse($response, "Failed to fetch issue #{$issueNumber} from {$repoFullName}");
    }

    /**
     * Check if GitHub issue is open
     */
    public function isIssueOpen(string $repoFullName, int $issueNumber): bool
    {
        try {
            $issue = $this->getIssue($repoFullName, $issueNumber);
            return isset($issue['state']) && $issue['state'] === 'open';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all issues for a repository
     */
    public function getRepositoryIssues(string $repoFullName, array $params = []): array
    {
        $defaultParams = [
            'state' => 'open',
            'per_page' => 100
        ];
        $response = $this->createClient()->get("/repos/{$repoFullName}/issues", array_merge($defaultParams, $params));
        if ($response->failed()) {
            return [];
        }
        return $response->json() ?? [];
    }

    /**
     * Get repository collaborators
     */
    public function getRepositoryCollaborators(string $repoFullName): array
    {
        $response = $this->createClient()->get("/repos/{$repoFullName}/collaborators");
        if ($response->failed()) {
            return [];
        }
        return $response->json() ?? [];
    }

    /**
     * Check if user has specific permissions on repository
     */
    public function getUserRepositoryPermissions(string $repoFullName, string $username): array
    {
        $response = $this->createClient()->get("/repos/{$repoFullName}/collaborators/{$username}/permission");
        if ($response->failed()) {
            return [];
        }
        return $response->json() ?? [];
    }

    /**
     * Get repository topics/tags
     */
    public function getRepositoryTopics(string $repoFullName): array
    {
        $response = $this->createClient()
            ->withHeaders(['Accept' => 'application/vnd.github.mercy-preview+json'])
            ->get("/repos/{$repoFullName}/topics");
        if ($response->failed()) {
            return [];
        }
        $data = $response->json() ?? [];
        return $data['names'] ?? [];
    }

    /**
     * Get repository README content
     */
    public function getRepositoryReadme(string $repoFullName): ?string
    {
        $response = $this->createClient()->get("/repos/{$repoFullName}/readme");
        if ($response->failed()) {
            return null;
        }
        $data = $response->json() ?? [];
        if (isset($data['content']) && isset($data['encoding']) && $data['encoding'] === 'base64') {
            return base64_decode($data['content']);
        }
        return null;
    }

    /**
     * Get repository statistics
     */
    public function getRepositoryStats(string $repoFullName): array
    {
        $response = $this->createClient()->get("/repos/{$repoFullName}/stats/contributors");
        if ($response->failed()) {
            return [];
        }
        return $response->json() ?? [];
    }

    /**
     * Parse GitHub repository URL to extract owner and repository name
     */
    public static function parseGitHubUrl(string $url): ?array
    {
        $url = trim($url);
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        $patterns = [
            '/^https?:\/\/github\.com\/([^\/\s]+)\/([^\/\s]+)(?:\.git)?(?:\/.*)?$/i',
            '/github\.com\/([^\/\s]+)\/([^\/\s]+)(?:\.git)?(?:\/.*)?$/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return [
                    'owner' => trim($matches[1]),
                    'name' => trim(rtrim($matches[2], '.git')),
                    'full_name' => trim($matches[1]) . '/' . trim(rtrim($matches[2], '.git'))
                ];
            }
        }

        return null;
    }

    /**
     * Parse GitHub issue URL to extract repo info and issue number
     */
    public static function parseGitHubIssueUrl(string $url): ?array
    {
        $url = trim($url);
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        $pattern = '/^https:\/\/github\.com\/([^\/]+)\/([^\/]+)\/issues\/(\d+)(?:\/.*)?$/i';

        if (preg_match($pattern, $url, $matches)) {
            return [
                'owner' => trim($matches[1]),
                'name' => trim($matches[2]),
                'issue_number' => (int) $matches[3],
                'repo_full_name' => trim($matches[1]) . '/' . trim($matches[2])
            ];
        }

        return null;
    }

    /**
     * Parse GitHub pull request URL
     */
    public static function parseGitHubPullRequestUrl(string $url): ?array
    {
        $url = trim($url);
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        $pattern = '/^https:\/\/github\.com\/([^\/]+)\/([^\/]+)\/pull\/(\d+)(?:\/.*)?$/i';

        if (preg_match($pattern, $url, $matches)) {
            return [
                'owner' => trim($matches[1]),
                'name' => trim($matches[2]),
                'pr_number' => (int) $matches[3],
                'repo_full_name' => trim($matches[1]) . '/' . trim($matches[2])
            ];
        }

        return null;
    }

    /**
     * Validate GitHub URL format
     */
    public static function isValidGitHubUrl(string $url): bool
    {
        return self::parseGitHubUrl($url) !== null;
    }

    /**
     * Validate GitHub issue URL format
     */
    public static function isValidGitHubIssueUrl(string $url): bool
    {
        return self::parseGitHubIssueUrl($url) !== null;
    }

    private function handleResponse(Response $response, string $errorMessage): array
    {
        if ($response->failed()) {
            throw new \Exception("{$errorMessage}: HTTP {$response->status()}");
        }
        return $response->json() ?? [];
    }

    public function hasValidToken(): bool
    {
        return !empty($this->user->oauth_provider_token);
    }
}

<?php

namespace App\Services;

use App\Models\UserProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GitHubApiService implements GitProviderInterface
{
    private UserProvider $provider;
    private const BASE_URL = 'https://api.github.com';
    private const USER_AGENT = 'GitCodeGud-App';
    private const API_VERSION = 'application/vnd.github.v3+json';
    private const GITHUB_REPO_PATTERN = '/^https?:\/\/github\.com\/([^\/\s]+)\/([^\/\s]+)(?:\.git)?(?:\/.*)?$/i';
    private const GITHUB_ISSUE_PATTERN = '/^https:\/\/github\.com\/([^\/]+)\/([^\/]+)\/issues\/(\d+)(?:\/.*)?$/i';
    private const GITHUB_PR_PATTERN = '/^https:\/\/github\.com\/([^\/]+)\/([^\/]+)\/pull\/(\d+)(?:\/.*)?$/i';

    public function __construct(UserProvider $provider)
    {
        $this->provider = $provider;
    }


    private function createClient(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'token ' . $this->provider->token,
            'Accept' => self::API_VERSION,
            'User-Agent' => self::USER_AGENT,
        ])->baseUrl(self::BASE_URL);
    }

    private static function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }
        return $url;
    }

    private function handleResponse(Response $response, string $errorMessage): array
    {
        if ($response->failed()) {
            throw new \Exception("{$errorMessage}: HTTP {$response->status()}");
        }
        return $response->json() ?? [];
    }

    private function handleSimpleResponse(Response $response): array
    {
        if ($response->failed()) {
            return [];
        }
        return $response->json() ?? [];
    }

    private static function parseGitHubUrlWithPattern(string $url, string $pattern, array $fieldMapping): ?array
    {
        $url = self::normalizeUrl($url);

        if (preg_match($pattern, $url, $matches)) {
            $result = [];
            foreach ($fieldMapping as $index => $fieldName) {
                if (isset($matches[$index])) {
                    $value = trim($matches[$index]);

                    if ($fieldName === 'name' && str_ends_with($value, '.git')) {
                        $value = substr($value, 0, -4);
                    }

                    $result[$fieldName] = $fieldName === 'issue_number' || $fieldName === 'pr_number'
                        ? (int)$value
                        : $value;
                }
            }

            if (isset($result['owner']) && isset($result['name'])) {
                $fullName = $result['owner'] . '/' . $result['name'];
                $result['full_name'] = $fullName;
                $result['repo_full_name'] = $fullName;
            }

            return $result;
        }

        return null;
    }

    public function getRepositoryIssues(string $repoFullName, array $params = []): array
    {
        $defaultParams = [
            'state' => 'open',
            'per_page' => 50,
            'sort' => 'updated',
            'direction' => 'desc'
        ];

        $response = $this->createClient()->get("/repos/{$repoFullName}/issues", array_merge($defaultParams, $params));
        return $this->handleSimpleResponse($response);
    }

    public static function parseGitUrl(string $url): ?array
    {
        $fieldMapping = [1 => 'owner', 2 => 'name'];
        return self::parseGitHubUrlWithPattern($url, self::GITHUB_REPO_PATTERN, $fieldMapping);
    }

    public static function parseGitIssueUrl(string $url): ?array
    {
        $fieldMapping = [1 => 'owner', 2 => 'name', 3 => 'issue_number'];
        return self::parseGitHubUrlWithPattern($url, self::GITHUB_ISSUE_PATTERN, $fieldMapping);
    }

    public static function parseGitPullRequestUrl(string $url): ?array
    {
        $fieldMapping = [1 => 'owner', 2 => 'name', 3 => 'pr_number'];
        return self::parseGitHubUrlWithPattern($url, self::GITHUB_PR_PATTERN, $fieldMapping);
    }

    public static function isValidGitUrl(string $url): bool
    {
        return self::parseGitUrl($url) !== null;
    }

    public static function isValidGitIssueUrl(string $url): bool
    {
        return self::parseGitIssueUrl($url) !== null;
    }

    public function hasValidToken(): bool
    {
        return !empty($this->provider->token);
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
        return $this->handleSimpleResponse($response);
    }

    public function getRepository(string $repoFullName): array
    {
        $response = $this->createClient()->get("/repos/{$repoFullName}");
        return $this->handleResponse($response, "Failed to fetch repository: {$repoFullName}");
    }

    public function isIssueOpen(string $repoFullName, int $issueNumber): bool
    {
        $response = $this->createClient()->get("/repos/{$repoFullName}/issues/{$issueNumber}");
        $data = $this->handleSimpleResponse($response);

        return isset($data['state']) && $data['state'] === 'open';
    }

    public function getIssueComments(string $repoFullName, int $issueNumber): array
    {
        $response = $this->createClient()->get("/repos/{$repoFullName}/issues/{$issueNumber}/comments");
        return $this->handleSimpleResponse($response);
    }

    public function getIssueCommentsByUrl(string $issueUrl): array
    {
        $issueInfo = self::parseGitIssueUrl($issueUrl);

        if (!$issueInfo) {
            return [];
        }

        $repoFullName = $issueInfo['owner'] . '/' . $issueInfo['name'];
        return $this->getIssueComments($repoFullName, $issueInfo['issue_number']);
    }

    public static function isValidGitPullRequestUrl(string $url): bool
    {
        return self::parseGitPullRequestUrl($url) !== null;
    }

    public function getPullRequest(string $repoFullName, int $prNumber): array
    {
        $response = $this->createClient()->get("/repos/{$repoFullName}/pulls/{$prNumber}");
        return $this->handleSimpleResponse($response);
    }

    public function isPullRequestOpen(string $repoFullName, int $prNumber): bool
    {
        $response = $this->createClient()->get("/repos/{$repoFullName}/pulls/{$prNumber}");
        $data = $this->handleSimpleResponse($response);

        return isset($data['state']) && $data['state'] === 'open';
    }

    public function getPullRequestComments(string $repoFullName, int $prNumber): array
    {
        $response = $this->createClient()->get("/repos/{$repoFullName}/pulls/{$prNumber}/comments");
        return $this->handleSimpleResponse($response);
    }
}

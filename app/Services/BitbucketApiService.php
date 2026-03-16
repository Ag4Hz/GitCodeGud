<?php

namespace App\Services;

use App\Models\UserProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BitbucketApiService implements GitProviderInterface
{
    private UserProvider $provider;
    private array $repoCache = [];

    private const BASE_URL = 'https://api.bitbucket.org/2.0';
    private const USER_AGENT = 'GitCodeGud-App';
    private const BITBUCKET_REPO_PATTERN = '/^https?:\/\/bitbucket\.org\/([^\/\s]+)\/([^\/\s]+)(?:\.git)?(?:\/.*)?$/i';
    private const BITBUCKET_ISSUE_PATTERN = '/^https:\/\/bitbucket\.org\/([^\/]+)\/([^\/]+)\/issues\/(\d+)(?:\/.*)?$/i';
    private const BITBUCKET_PR_PATTERN = '/^https:\/\/bitbucket\.org\/([^\/]+)\/([^\/]+)\/pull-requests\/(\d+)(?:\/.*)?$/i';

    public function __construct(UserProvider $provider)
    {
        $this->provider = $provider;
    }

    public function getProviderKey(): string
    {
        return 'bitbucket';
    }

    private function createClient(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->provider->token,
            'Accept'        => 'application/json',
            'User-Agent'    => self::USER_AGENT,
        ])->baseUrl(self::BASE_URL);
    }

    private function refreshToken(): bool
    {
        if (empty($this->provider->refresh_token)) {
            return false;
        }

        $clientId     = config('services.bitbucket.client_id');
        $clientSecret = config('services.bitbucket.client_secret');

        if (!$clientId || !$clientSecret) {
            return false;
        }

        $response = Http::asForm()->post('https://bitbucket.org/site/oauth2/access_token', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->provider->refresh_token,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
        ]);

        if ($response->failed()) {
            return false;
        }

        $data = $response->json();
        if (empty($data['access_token'])) {
            return false;
        }

        $this->provider->update([
            'token'         => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $this->provider->refresh_token,
        ]);

        return true;
    }

    private function getWithRefresh(string $url, array $params = []): Response
    {
        $response = $this->createClient()->get($url, $params);
        if ($response->status() === 401 && $this->refreshToken()) {
            $response = $this->createClient()->get($url, $params);
        }
        return $response;
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

    private static function parseUrlWithPattern(string $url, string $pattern, array $fieldMapping): ?array
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
                    $result[$fieldName] = ($fieldName === 'issue_number' || $fieldName === 'pr_number')
                        ? (int) $value
                        : $value;
                }
            }
            if (isset($result['owner']) && isset($result['name'])) {
                $fullName                 = $result['owner'] . '/' . $result['name'];
                $result['full_name']      = $fullName;
                $result['repo_full_name'] = $fullName;
            }
            return $result;
        }

        return null;
    }

    public static function parseGitUrl(string $url): ?array
    {
        return self::parseUrlWithPattern($url, self::BITBUCKET_REPO_PATTERN, [1 => 'owner', 2 => 'name']);
    }

    public static function parseGitIssueUrl(string $url): ?array
    {
        return self::parseUrlWithPattern($url, self::BITBUCKET_ISSUE_PATTERN, [1 => 'owner', 2 => 'name', 3 => 'issue_number']);
    }

    public static function parseGitPullRequestUrl(string $url): ?array
    {
        return self::parseUrlWithPattern($url, self::BITBUCKET_PR_PATTERN, [1 => 'owner', 2 => 'name', 3 => 'pr_number']);
    }

    public static function isValidGitUrl(string $url): bool
    {
        return self::parseGitUrl($url) !== null;
    }

    public static function isValidGitIssueUrl(string $url): bool
    {
        return self::parseGitIssueUrl($url) !== null;
    }

    public static function isValidGitPullRequestUrl(string $url): bool
    {
        return self::parseGitPullRequestUrl($url) !== null;
    }

    public function hasValidToken(): bool
    {
        return !empty($this->provider->token);
    }

    public function getUserRepositories(array $params = []): array
    {
        $defaultParams = [
            'role'    => 'member',
            'sort'    => '-updated_on',
            'pagelen' => 100,
        ];

        $response = $this->getWithRefresh('/repositories', array_merge($defaultParams, $params));
        $data     = $this->handleSimpleResponse($response);
        $repos    = $data['values'] ?? [];

        foreach ($repos as $repo) {
            $ws   = $repo['workspace']['slug'] ?? $repo['owner']['nickname'] ?? null;
            $slug = $repo['slug'] ?? null;
            if ($ws && $slug) {
                $this->repoCache["{$ws}/{$slug}"] = $repo;
            }
        }

        return $repos;
    }

    public function getRepositoryLanguages(string $repoFullName): array
    {
        $repo     = $this->repoCache[$repoFullName] ?? $this->getRepository($repoFullName);
        $language = $repo['language'] ?? null;

        if (empty($language)) {
            return [];
        }

        return [ucfirst(strtolower($language)) => 1000];
    }

    public function getRepository(string $repoFullName): array
    {
        if (!isset($this->repoCache[$repoFullName])) {
            $response                       = $this->createClient()->get("/repositories/{$repoFullName}");
            $this->repoCache[$repoFullName] = $this->handleResponse($response, "Failed to fetch repository: {$repoFullName}");
        }
        return $this->repoCache[$repoFullName];
    }

    public function getRepositoryIssues(string $repoFullName, array $params = []): array
    {
        $defaultParams = ['state' => 'new', 'pagelen' => 50, 'sort' => '-updated_on'];
        $response      = $this->createClient()->get("/repositories/{$repoFullName}/issues", array_merge($defaultParams, $params));
        $data          = $this->handleSimpleResponse($response);
        return $data['values'] ?? [];
    }

    public function isIssueOpen(string $repoFullName, int $issueNumber): bool
    {
        $response = $this->createClient()->get("/repositories/{$repoFullName}/issues/{$issueNumber}");
        $data     = $this->handleSimpleResponse($response);
        return isset($data['state']) && in_array($data['state'], ['new', 'open']);
    }

    public function getIssueComments(string $repoFullName, int $issueNumber): array
    {
        $response = $this->createClient()->get("/repositories/{$repoFullName}/issues/{$issueNumber}/comments");
        $data     = $this->handleSimpleResponse($response);
        return $data['values'] ?? [];
    }

    public function getIssueCommentsByUrl(string $issueUrl): array
    {
        $issueInfo = self::parseGitIssueUrl($issueUrl);
        if (!$issueInfo) {
            return [];
        }
        return $this->getIssueComments($issueInfo['owner'] . '/' . $issueInfo['name'], $issueInfo['issue_number']);
    }

    public function getPullRequest(string $repoFullName, int $prNumber): array
    {
        $response = $this->createClient()->get("/repositories/{$repoFullName}/pullrequests/{$prNumber}");
        return $this->handleSimpleResponse($response);
    }

    public function isPullRequestOpen(string $repoFullName, int $prNumber): bool
    {
        $response = $this->createClient()->get("/repositories/{$repoFullName}/pullrequests/{$prNumber}");
        $data     = $this->handleSimpleResponse($response);
        return isset($data['state']) && $data['state'] === 'OPEN';
    }

    public function getPullRequestComments(string $repoFullName, int $prNumber): array
    {
        $response = $this->createClient()->get("/repositories/{$repoFullName}/pullrequests/{$prNumber}/comments");
        $data     = $this->handleSimpleResponse($response);
        return $data['values'] ?? [];
    }

    public function canUserWriteToRepository(string $repoFullName): bool
    {
        if (!$this->hasValidToken()) {
            return false;
        }

        $repo = $this->getRepository($repoFullName);
        return !empty($repo);
    }
}

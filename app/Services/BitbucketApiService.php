<?php

namespace App\Services;

use App\Models\UserProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BitbucketApiService implements GitProviderInterface
{
    private UserProvider $provider;

    private const BASE_URL = 'https://api.bitbucket.org/2.0';
    private const USER_AGENT = 'GitCodeGud-App';
    private const BITBUCKET_REPO_PATTERN = '/^https?:\/\/bitbucket\.org\/([^\/\s]+)\/([^\/\s]+)(?:\.git)?(?:\/.*)?$/i';
    private const BITBUCKET_ISSUE_PATTERN = '/^https:\/\/bitbucket\.org\/([^\/]+)\/([^\/]+)\/issues\/(\d+)(?:\/.*)?$/i';
    private const BITBUCKET_PR_PATTERN = '/^https:\/\/bitbucket\.org\/([^\/]+)\/([^\/]+)\/pull-requests\/(\d+)(?:\/.*)?$/i';

    public function __construct(UserProvider $provider) {
        $this->provider = $provider;
    }

    private function createClient(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->provider->token,
            'Accept' => 'application/json',
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
                $fullName = $result['owner'] . '/' . $result['name'];
                $result['full_name'] = $fullName;
                $result['repo_full_name'] = $fullName;
            }

            return $result;
        }

        return null;
    }

    public static function parseGitUrl(string $url): ?array
    {
        $fieldMapping = [1 => 'owner', 2 => 'name'];
        return self::parseUrlWithPattern($url, self::BITBUCKET_REPO_PATTERN, $fieldMapping);
    }

    public static function parseGitIssueUrl(string $url): ?array
    {
        $fieldMapping = [1 => 'owner', 2 => 'name', 3 => 'issue_number'];
        return self::parseUrlWithPattern($url, self::BITBUCKET_ISSUE_PATTERN, $fieldMapping);
    }

    public static function parseGitPullRequestUrl(string $url): ?array
    {
        $fieldMapping = [1 => 'owner', 2 => 'name', 3 => 'pr_number'];
        return self::parseUrlWithPattern($url, self::BITBUCKET_PR_PATTERN, $fieldMapping);
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
            'role' => 'owner',
            'sort' => '-updated_on',
            'pagelen' => 100
        ];
        $response = $this->createClient()->get('/repositories/' . $this->provider->provider_id, array_merge($defaultParams, $params));
        $data = $this->handleResponse($response, 'Failed to fetch Bitbucket repositories');

        return $data['values'] ?? [];
    }

    public function getRepositoryLanguages(string $repoFullName): array
    {
        // Bitbucket doesn't have a direct languages endpoint
        // Return empty array or implement custom logic
        return [];
    }

    public function getRepository(string $repoFullName): array
    {
        $response = $this->createClient()->get("/repositories/{$repoFullName}");
        return $this->handleResponse($response, "Failed to fetch repository: {$repoFullName}");
    }

    public function getRepositoryIssues(string $repoFullName, array $params = []): array
    {
        $defaultParams = [
            'state' => 'new',
            'pagelen' => 50,
            'sort' => '-updated_on'
        ];

        $response = $this->createClient()->get("/repositories/{$repoFullName}/issues", array_merge($defaultParams, $params));
        $data = $this->handleSimpleResponse($response);

        return $data['values'] ?? [];
    }

    public function isIssueOpen(string $repoFullName, int $issueNumber): bool
    {
        $response = $this->createClient()->get("/repositories/{$repoFullName}/issues/{$issueNumber}");
        $data = $this->handleSimpleResponse($response);

        return isset($data['state']) && in_array($data['state'], ['new', 'open']);
    }

    public function getIssueComments(string $repoFullName, int $issueNumber): array
    {
        $response = $this->createClient()->get("/repositories/{$repoFullName}/issues/{$issueNumber}/comments");
        $data = $this->handleSimpleResponse($response);

        return $data['values'] ?? [];
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

    public function getPullRequest(string $repoFullName, int $prNumber): array
    {
        $response = $this->createClient()->get("/repositories/{$repoFullName}/pullrequests/{$prNumber}");
        return $this->handleSimpleResponse($response);
    }

    public function isPullRequestOpen(string $repoFullName, int $prNumber): bool
    {
        $response = $this->createClient()->get("/repositories/{$repoFullName}/pullrequests/{$prNumber}");
        $data = $this->handleSimpleResponse($response);

        return isset($data['state']) && $data['state'] === 'OPEN';
    }

    public function getPullRequestComments(string $repoFullName, int $prNumber): array
    {
        $response = $this->createClient()->get("/repositories/{$repoFullName}/pullrequests/{$prNumber}/comments");
        $data = $this->handleSimpleResponse($response);

        return $data['values'] ?? [];
    }

    public function canUserWriteToRepository(string $repoFullName): bool
    {
        // Bitbucket permissions are more complex; keep this conservative.
        // If we can fetch the repo and it isn't read-only, treat as writable.
        try {
            $repo = $this->getRepository($repoFullName);

            // Common fields: "is_private", "scm", etc. No reliable write flag in this minimal integration.
            // Return true if repo exists and token is present.
            return $this->hasValidToken() && !empty($repo);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

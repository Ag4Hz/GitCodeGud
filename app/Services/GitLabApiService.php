<?php

namespace App\Services;

use App\Models\UserProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GitLabApiService implements GitProviderInterface
{
    private UserProvider $provider;
    private const BASE_URL = 'https://gitlab.com/api/v4';
    private const USER_AGENT = 'GitCodeGud-App';
    private const GITLAB_REPO_PATTERN = '/^https?:\/\/gitlab\.com\/([^\/\s]+(?:\/[^\/\s]+)*)(?:\.git)?(?:\/.*)?$/i';
    private const GITLAB_ISSUE_PATTERN = '/^https:\/\/gitlab\.com\/([^\/]+(?:\/[^\/]+)*)\/-\/issues\/(\d+)(?:\/.*)?$/i';
    private const GITLAB_MR_PATTERN = '/^https:\/\/gitlab\.com\/([^\/]+(?:\/[^\/]+)*)\/-\/merge_requests\/(\d+)(?:\/.*)?$/i';

    public function __construct(UserProvider $provider)
    {
        $this->provider = $provider;
    }

    public function getProviderKey(): string
    {
        return 'gitlab';
    }

    private function createClient(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->provider->token,
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

    private static function encodeProjectPath(string $path): string
    {
        return str_replace('/', '%2F', $path);
    }

    public static function parseGitUrl(string $url): ?array
    {
        $url = self::normalizeUrl($url);

        if (preg_match(self::GITLAB_REPO_PATTERN, $url, $matches)) {
            $fullPath = trim($matches[1]);

            if (str_ends_with($fullPath, '.git')) {
                $fullPath = substr($fullPath, 0, -4);
            }

            $parts = explode('/', $fullPath);
            $name = array_pop($parts);
            $owner = implode('/', $parts);

            return [
                'owner' => $owner,
                'name' => $name,
                'full_name' => $fullPath,
                'repo_full_name' => $fullPath,
            ];
        }

        return null;
    }

    public static function parseGitIssueUrl(string $url): ?array
    {
        $url = self::normalizeUrl($url);

        if (preg_match(self::GITLAB_ISSUE_PATTERN, $url, $matches)) {
            $fullPath = trim($matches[1]);
            $issueNumber = (int)$matches[2];

            $parts = explode('/', $fullPath);
            $name = array_pop($parts);
            $owner = implode('/', $parts);

            return [
                'owner' => $owner,
                'name' => $name,
                'full_name' => $fullPath,
                'repo_full_name' => $fullPath,
                'issue_number' => $issueNumber,
            ];
        }

        return null;
    }

    public static function parseGitPullRequestUrl(string $url): ?array
    {
        $url = self::normalizeUrl($url);

        if (preg_match(self::GITLAB_MR_PATTERN, $url, $matches)) {
            $fullPath = trim($matches[1]);
            $mrNumber = (int)$matches[2];

            $parts = explode('/', $fullPath);
            $name = array_pop($parts);
            $owner = implode('/', $parts);

            return [
                'owner' => $owner,
                'name' => $name,
                'full_name' => $fullPath,
                'repo_full_name' => $fullPath,
                'pr_number' => $mrNumber,
            ];
        }

        return null;
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
            'membership' => true,
            'order_by' => 'updated_at',
            'sort' => 'desc',
            'per_page' => 100,
            'min_access_level' => 20
        ];
        $response = $this->createClient()->get('/projects', array_merge($defaultParams, $params));
        return $this->handleResponse($response, 'Failed to fetch GitLab projects');
    }

    public function getRepositoryLanguages(string $repoFullName): array
    {
        $encodedPath = self::encodeProjectPath($repoFullName);
        $response = $this->createClient()->get("/projects/{$encodedPath}/languages");
        return $this->handleSimpleResponse($response);
    }

    public function getRepository(string $repoFullName): array
    {
        $encodedPath = self::encodeProjectPath($repoFullName);
        $response = $this->createClient()->get("/projects/{$encodedPath}");
        return $this->handleResponse($response, "Failed to fetch project: {$repoFullName}");
    }

    public function canUserWriteToRepository(string $repoFullName): bool
    {
        try {
            $repoData = $this->getRepository($repoFullName);
            $accessLevel = $repoData['permissions']['project_access']['access_level']
                ?? $repoData['permissions']['group_access']['access_level']
                ?? 0;
            return $accessLevel >= 30;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getRepositoryIssues(string $repoFullName, array $params = []): array
    {
        if (isset($params['state']) && $params['state'] === 'open') {
            $params['state'] = 'opened';
        }

        $defaultParams = [
            'state' => 'opened',
            'per_page' => 50,
            'order_by' => 'updated_at',
            'sort' => 'desc'
        ];

        $encodedPath = self::encodeProjectPath($repoFullName);
        $response = $this->createClient()->get("/projects/{$encodedPath}/issues", array_merge($defaultParams, $params));
        return $this->handleSimpleResponse($response);
    }

    public function isIssueOpen(string $repoFullName, int $issueNumber): bool
    {
        $encodedPath = self::encodeProjectPath($repoFullName);
        $response = $this->createClient()->get("/projects/{$encodedPath}/issues/{$issueNumber}");
        $data = $this->handleSimpleResponse($response);

        return isset($data['state']) && $data['state'] === 'opened';
    }

    public function getIssueComments(string $repoFullName, int $issueNumber): array
    {
        $encodedPath = self::encodeProjectPath($repoFullName);
        $response = $this->createClient()->get("/projects/{$encodedPath}/issues/{$issueNumber}/notes");
        $notes = $this->handleSimpleResponse($response);

        return array_values(array_filter($notes, function ($note) {
            return empty($note['system']);
        }));
    }

    public function getIssueCommentsByUrl(string $issueUrl): array
    {
        $issueInfo = self::parseGitIssueUrl($issueUrl);

        if (!$issueInfo) {
            return [];
        }

        return $this->getIssueComments($issueInfo['full_name'], $issueInfo['issue_number']);
    }

    public function getPullRequest(string $repoFullName, int $prNumber): array
    {
        $encodedPath = self::encodeProjectPath($repoFullName);
        $response = $this->createClient()->get("/projects/{$encodedPath}/merge_requests/{$prNumber}");
        return $this->handleSimpleResponse($response);
    }

    public function isPullRequestOpen(string $repoFullName, int $prNumber): bool
    {
        $encodedPath = self::encodeProjectPath($repoFullName);
        $response = $this->createClient()->get("/projects/{$encodedPath}/merge_requests/{$prNumber}");
        $data = $this->handleSimpleResponse($response);

        return isset($data['state']) && $data['state'] === 'opened';
    }

    public function getPullRequestComments(string $repoFullName, int $prNumber): array
    {
        $encodedPath = self::encodeProjectPath($repoFullName);
        $response = $this->createClient()->get("/projects/{$encodedPath}/merge_requests/{$prNumber}/notes");
        $notes = $this->handleSimpleResponse($response);

        return array_values(array_filter($notes, function ($note) {
            return empty($note['system']);
        }));
    }
}

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

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class JiraApiService
{
    private const JIRA_CLOUD_PATTERN = '/^https:\/\/([a-zA-Z0-9\-]+)\.atlassian\.net\/browse\/([A-Z][A-Z0-9_]+-\d+)$/i';

    public function __construct(
        private string $cloudId,
        private string $token,
    ) {}

    public static function parseIssueUrl(string $url): ?array
    {
        $url = trim($url);
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        if (preg_match(self::JIRA_CLOUD_PATTERN, $url, $matches)) {
            return [
                'workspace' => $matches[1],
                'issue_key' => strtoupper($matches[2]),
            ];
        }

        return null;
    }

    public function getIssueComments(string $issueKey): array
    {
        $response = Http::withToken($this->token)
            ->acceptJson()
            ->get("https://api.atlassian.com/ex/jira/{$this->cloudId}/rest/api/3/issue/{$issueKey}/comment", [
                'maxResults' => 100,
                'orderBy'    => 'created',
            ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json()['comments'] ?? [];
    }

    public function getIssueCommentsByUrl(string $issueUrl): array
    {
        $issueInfo = self::parseIssueUrl($issueUrl);
        if (!$issueInfo) {
            return [];
        }

        $cloudId = $this->resolveCloudId($issueInfo['workspace']);
        if ($cloudId) {
            $this->cloudId = $cloudId;
        }

        return $this->getIssueComments($issueInfo['issue_key']);
    }

    public static function isValidIssueUrl(string $url): bool
    {
        return self::parseIssueUrl($url) !== null;
    }

    private function refreshToken(): bool
    {
        $clientId     = config('services.atlassian.client_id');
        $clientSecret = config('services.atlassian.client_secret');

        if (!$clientId || !$clientSecret) {
            return false;
        }

        $provider = \App\Models\UserProvider::where('token', $this->token)
            ->where('provider', 'jira')
            ->first();

        if (!$provider || empty($provider->refresh_token)) {
            return false;
        }

        $response = Http::asForm()->post('https://auth.atlassian.com/oauth/token', [
            'grant_type'    => 'refresh_token',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $provider->refresh_token,
        ]);

        if ($response->failed()) {
            return false;
        }

        $data = $response->json();
        if (empty($data['access_token'])) {
            return false;
        }

        $provider->update([
            'token'         => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $provider->refresh_token,
        ]);

        $this->token = $data['access_token'];
        return true;
    }

    private function doGetIssue(string $issueKey): \Illuminate\Http\Client\Response
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->get("https://api.atlassian.com/ex/jira/{$this->cloudId}/rest/api/3/issue/{$issueKey}", [
                'fields' => 'summary,status,assignee',
            ]);
    }

    public function getIssue(string $issueKey): array
    {
        $response = $this->doGetIssue($issueKey);

        if ($response->status() === 401 && $this->refreshToken()) {
            $response = $this->doGetIssue($issueKey);
        }

        if ($response->failed()) {
            return [];
        }

        return $response->json() ?? [];
    }

    private function resolveCloudId(string $workspace): ?string
    {
        $response = Http::withToken($this->token)
            ->acceptJson()
            ->get('https://api.atlassian.com/oauth/token/accessible-resources');

        if ($response->failed()) {
            return null;
        }

        foreach ($response->json() as $site) {
            if (str_contains($site['url'] ?? '', $workspace)) {
                return $site['id'];
            }
        }

        return null;
    }

    // Jira status category  (To Do)(In Progress)
    public function isIssueOpen(string $workspace, string $issueKey): bool
    {
        $cloudId = $this->resolveCloudId($workspace);

        if ($cloudId) {
            $this->cloudId = $cloudId;
        }

        $issue          = $this->getIssue($issueKey);
        $statusCategory = $issue['fields']['status']['statusCategory']['key'] ?? null;

        return in_array($statusCategory, ['new', 'indeterminate'], true);
    }

    public static function fromProvider(\App\Models\UserProvider $provider): self
    {
        return new self(
            cloudId: $provider->provider_id,
            token:   $provider->token,
        );
    }
}

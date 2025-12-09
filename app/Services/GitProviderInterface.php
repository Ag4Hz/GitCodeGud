<?php
namespace App\Services;

interface GitProviderInterface
{
    // Static URL parsing / validation helpers
    public static function parseGitUrl(string $url): ?array;
    public static function parseGitIssueUrl(string $url): ?array;
    public static function parseGitPullRequestUrl(string $url): ?array;

    public static function isValidGitUrl(string $url): bool;
    public static function isValidGitIssueUrl(string $url): bool;
    public static function isValidGitPullRequestUrl(string $url): bool;

    // Token check
    public function hasValidToken(): bool;

    // Repository endpoints
    public function getUserRepositories(array $params = []): array;
    public function getRepositoryLanguages(string $repoFullName): array;
    public function getRepository(string $repoFullName): array;
    public function getRepositoryIssues(string $repoFullName, array $params = []): array;
    public function canUserWriteToRepository(string $repoFullName): bool;

    // Issue endpoints
    public function isIssueOpen(string $repoFullName, int $issueNumber): bool;
    public function getIssueComments(string $repoFullName, int $issueNumber): array;
    public function getIssueCommentsByUrl(string $issueUrl): array;

    // Pull request endpoints
    public function getPullRequest(string $repoFullName, int $prNumber): array;
    public function isPullRequestOpen(string $repoFullName, int $prNumber): bool;
    public function getPullRequestComments(string $repoFullName, int $prNumber): array;
}

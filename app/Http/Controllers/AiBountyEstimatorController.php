<?php

namespace App\Http\Controllers;

use App\Services\AiBountyEstimatorService;
use App\Services\GitProviderFactory;
use App\Services\GitRepoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiBountyEstimatorController extends Controller
{
    public function __construct(
        private readonly AiBountyEstimatorService $estimator
    ) {}

    public function estimate(Request $request): JsonResponse
    {
        $request->validate([
            'issue_title' => ['required', 'string', 'max:500'],
            'issue_body' => ['nullable', 'string'],
            'issue_url' => ['nullable', 'url'],
            'repo_full_name' => ['nullable', 'string'],
            'provider' => ['nullable', 'string', 'in:github,gitlab,bitbucket'],
        ]);

        $user = $request->user();
        $provider = $request->input('provider', 'github');
        $repoFullName = $request->input('repo_full_name');
        $issueTitle = $request->input('issue_title', '');
        $issueBody = $request->input('issue_body', '');

        $languages = [];

        if ($user && $repoFullName) {
            try {
                $repoService = new GitRepoService($user);

                if ($repoService->hasProvider($provider)) {
                    $languages = $repoService->getRepositoryLanguages($provider, $repoFullName);
                }
            } catch (\Throwable) {
            }
        }

        if (empty($issueBody) && $request->filled('issue_url') && $user && $repoFullName) {
            try {
                $issueBody = $this->fetchIssueBody(
                    $user,
                    $provider,
                    $repoFullName,
                    $request->input('issue_url')
                );
            } catch (\Throwable) {
            }
        }

        try {
            $result = $this->estimator->estimate(
                issueTitle: $issueTitle,
                issueBody: $issueBody,
                languages: $languages,
                provider: $provider,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        }

        return response()->json($result);
    }

    private function fetchIssueBody(
        $user,
        string $provider,
        string $repoFullName,
        string $issueUrl
    ): string {
        $userProvider = $user->providers()->where('provider', $provider)->first();
        if (! $userProvider || ! $userProvider->token) {
            return '';
        }

        $api = GitProviderFactory::getProvider($provider, $userProvider);

        preg_match('/\/issues\/(\d+)|issues\/(\d+)|browse\/([A-Z]+-\d+)/i', $issueUrl, $m);
        $issueNumber = (int) ($m[1] ?? $m[2] ?? 0);
        if (! $issueNumber) {
            return '';
        }

        $issues = $api->getRepositoryIssues($repoFullName, [
            'state' => 'open',
            'per_page' => 1,
        ]);

        foreach ($issues as $issue) {
            $num = $issue['number'] ?? $issue['iid'] ?? null;
            if ((int) $num === $issueNumber) {
                return $issue['body'] ?? $issue['description'] ?? '';
            }
        }

        return '';
    }
}

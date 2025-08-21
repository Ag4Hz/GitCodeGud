<?php

namespace App\Http\Controllers;

use App\Http\Requests\BountyStoreRequest;
use App\Models\Bounty;
use App\Models\Issue;
use App\Models\Repo;
use App\Services\GitHubApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BountyController extends Controller
{
    /**
     * Store a newly created bounty in storage.
     */
    public function store(BountyStoreRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $user = $request->user();

            $repoInfo = $this->parseGitHubUrl($validated['repo_url']);
            if (!$repoInfo) {
                return back()->withErrors(['repo_url' => 'Invalid GitHub repository URL format.']);
            }

            $gitId = $repoInfo['owner'] . '/' . $repoInfo['name'];

            $repo = Repo::updateOrCreate(
                [
                    'url' => $validated['repo_url'],
                ],
                [
                    'user_id' => $user->id,
                    'description' => "Repository for {$repoInfo['owner']}/{$repoInfo['name']}",
                    'git_id' => $gitId,
                ]
            );

            $githubApi = new GitHubApiService($user);
            $repoLanguages = [];

            if ($githubApi->hasValidToken()) {
                try {
                    $languageStats = $githubApi->getRepositoryLanguages($gitId);
                    $repoLanguages = collect($languageStats)
                        ->sortDesc()
                        ->keys()
                        ->toArray();
                } catch (\Exception $e) {
                    $repoLanguages = [];
                }
            }

            $issueNumber = $this->parseGitHubIssueUrl($validated['issue_url']);
            if (!$issueNumber) {
                return back()->withErrors(['issue_url' => 'Invalid GitHub issue URL format.']);
            }

            $existingIssue = DB::table('issues')
                ->where('url', $validated['issue_url'])
                ->where('repo_id', $repo->id)
                ->first();

            if ($existingIssue) {
                $issue = Issue::find($existingIssue->id);
            } else {
                $issueId = DB::table('issues')->insertGetId([
                    'repo_id' => $repo->id,
                    'description' => $validated['description'] ?? '',
                    'url' => $validated['issue_url'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $issue = Issue::find($issueId);
            }

            // Check if bounty already exists for this issue
            $existingBounty = Bounty::where('issue_id', $issue->id)->first();
            if ($existingBounty) {
                return back()->withErrors(['issue_url' => 'A bounty already exists for this GitHub issue.']);
            }

            // Create bounty with detected languages
            $bountyData = [
                'issue_id' => $issue->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? '',
                'reward_xp' => $validated['reward_xp'],
                'status' => 'open',
                'languages' => $repoLanguages,
            ];

            $bounty = Bounty::create($bountyData);

            DB::commit();

            return redirect()->route('profile.show')
                ->with('success', 'Bounty created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors(['general' => 'Failed to create bounty: ' . $e->getMessage()]);
        }
    }

    private function parseGitHubUrl(string $url): ?array
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
                ];
            }
        }
        return null;
    }

    /**
     * Parse GitHub issue URL to extract issue number.
     */
    private function parseGitHubIssueUrl(string $url): ?int
    {
        $url = trim($url);
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        $patterns = [
            '/^https?:\/\/github\.com\/[^\/\s]+\/[^\/\s]+\/issues\/(\d+)(?:\/.*)?$/i',
            '/github\.com\/[^\/\s]+\/[^\/\s]+\/issues\/(\d+)(?:\/.*)?$/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return (int) $matches[1];
            }
        }
        return null;
    }
}

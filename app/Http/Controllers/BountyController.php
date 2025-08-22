<?php

namespace App\Http\Controllers;

use App\Http\Requests\BountyStoreRequest;
use App\Http\Requests\BountyUpdateRequest;
use App\Models\Bounty;
use App\Models\Issue;
use App\Models\Repo;
use App\Services\GitHubApiService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BountyController extends Controller
{
    /**
     * Store a newly created bounty in storage.
     */
    public function store(BountyStoreRequest $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();
            $user = $request->user();

            $repoInfo = GitHubApiService::parseGitHubUrl($validated['repo_url']);
            $issueInfo = GitHubApiService::parseGitHubIssueUrl($validated['issue_url']);

            if (!$user->can('createForRepository', [Bounty::class, $validated['repo_url']])) {
                DB::rollBack();
                return back()->withErrors([
                    'repo_url' => 'You can only create bounties for repositories you own or have push access to.'
                ]);
            }

            // Find or create repository using Eloquent
            $repo = Repo::updateOrCreate(
                ['url' => $validated['repo_url']],
                [
                    'user_id' => $user->id,
                    'description' => "Repository for {$repoInfo['full_name']}",
                    'git_id' => $repoInfo['full_name'],
                ]
            );

            // Get repository languages
            $githubApi = new GitHubApiService($user);
            $repoLanguages = [];

            if ($githubApi->hasValidToken()) {
                try {
                    $languageStats = $githubApi->getRepositoryLanguages($repoInfo['full_name']);
                    $repoLanguages = collect($languageStats)->sortDesc()->keys()->toArray();
                } catch (\Exception $e) {
                    $repoLanguages = [];
                }
            }

            // Find or create issue using Eloquent
            $issue = Issue::firstOrCreate(
                [
                    'url' => $validated['issue_url'],
                    'repo_id' => $repo->id,
                ],
                [
                    'description' => $validated['description'] ?? '',
                ]
            );

            // Create bounty - check if already exists
            $bounty = Bounty::firstOrCreate(
                [
                    'issue_id' => $issue->id,
                ],
                [
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? '',
                    'reward_xp' => $validated['reward_xp'],
                    'languages' => $repoLanguages,
                ]
            );

            if (!$bounty->wasRecentlyCreated) {
                DB::rollBack();
                return back()->withErrors([
                    'issue_url' => 'A bounty already exists for this GitHub issue.'
                ]);
            }

            DB::commit();

            return redirect()
                ->route('profile.show')
                ->with('success', 'Bounty created successfully!');

        } catch (QueryException $e) {
            DB::rollBack();
            return back()->withErrors([
                'issue_url' => 'A bounty already exists for this GitHub issue.'
            ]);
        }
    }

    /**
     * Show the bounty details.
     */
    public function show(Bounty $bounty): Response
    {
        return Inertia::render('bounties/Show', [
            'bounty' => $bounty->load(['issue.repo', 'submissions.user']),
        ]);
    }

    /**
     * Show the form for editing the specified bounty.
     */
    public function edit(Bounty $bounty): Response
    {
        $this->authorize('update', $bounty);

        return Inertia::render('bounties/Edit', [
            'bounty' => $bounty->load(['issue.repo']),
        ]);
    }

    /**
     * Update the specified bounty in storage.
     */
    public function update(BountyUpdateRequest $request, Bounty $bounty): RedirectResponse
    {
        if (!Gate::allows('update', $bounty)) {
            return back()->withErrors([
                'general' => 'You are not authorized to edit this bounty. Only the repository owner can edit bounties.'
            ]);
        }

        $validated = $request->validated();

        $bounty->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'reward_xp' => $validated['reward_xp'],
        ]);

        return redirect()
            ->route('profile.show')
            ->with('success', 'Bounty updated successfully!');
    }
}

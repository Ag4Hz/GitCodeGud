<?php

namespace App\Http\Controllers;

use App\Http\Requests\BountyStoreRequest;
use App\Http\Requests\BountyUpdateRequest;
use App\Models\Bounty;
use App\Models\Issue;
use App\Models\Repo;
use App\Services\GitHubApiService;
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
        $validated = $request->validated();
        $user = $request->user();

        $repoInfo = GitHubApiService::parseGitHubUrl($validated['repo_url']);

        return DB::transaction(function () use ($validated, $user, $repoInfo) {
            $repo = $this->findOrCreateRepo($validated['repo_url'], $user, $repoInfo);
            $issue = $this->findOrCreateIssue($validated['issue_url'], $repo->id, $validated['description']);
            $repoLanguages = $this->getRepositoryLanguages($user, $repoInfo['full_name']);

            Bounty::create([
                'issue_id' => $issue->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? '',
                'reward_xp' => $validated['reward_xp'],
                'languages' => $repoLanguages,
                'status' => 'open',
            ]);

        });

        return redirect()
            ->route('profile.show')
            ->with('success', 'Bounty created successfully!');
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

    /**
     * Soft delete the specified bounty.
     */
    public function destroy(string $id): RedirectResponse
    {
        $bounty = Bounty::findOrFail($id);

        if (!Gate::allows('delete', $bounty)) {
            return back()->withErrors([
                'general' => 'You are not authorized to delete this bounty. Only the repository owner can delete bounties.'
            ]);
        }

        $bounty->delete();

        return redirect()
            ->route('profile.show')
            ->with('success', 'Bounty archived successfully! You can restore it from your archived bounties.');
    }

    /**
     * Restore a soft deleted bounty.
     */
    public function restore(string $id): RedirectResponse
    {
        $bounty = Bounty::withTrashed()->findOrFail($id);

        if (!Gate::allows('restore', $bounty)) {
            return back()->withErrors([
                'general' => 'You are not authorized to restore this bounty. Only the repository owner can restore bounties.'
            ]);
        }

        $bounty->restore();

        return redirect()
            ->route('profile.show')
            ->with('success', 'Bounty restored successfully!');
    }

    /**
     * Get public bounties for search/popular lists (excludes soft deleted).
     */
    public function index()
    {
        $bounties = Bounty::with(['issue.repo'])
            ->active()
            ->where('status', 'open')
            ->latest()
            ->paginate(12);

        return Inertia::render('bounties/Index', [
            'bounties' => $bounties,
        ]);
    }

    /**
     * Find or create repository.
     */
    private function findOrCreateRepo(string $repoUrl, $user, array $repoInfo): Repo
    {
        return Repo::updateOrCreate(
            ['url' => $repoUrl],
            [
                'user_id' => $user->id,
                'description' => "Repository for {$repoInfo['full_name']}",
                'git_id' => $repoInfo['full_name'],
            ]
        );
    }

    /**
     * Find or create issue.
     */
    private function findOrCreateIssue(string $issueUrl, int $repoId, ?string $description): Issue
    {
        return Issue::firstOrCreate(
            ['url' => $issueUrl, 'repo_id' => $repoId],
            ['description' => $description ?? '']
        );
    }

    /**
     * Get repository programming languages from GitHub API.
     */
    private function getRepositoryLanguages($user, string $repoFullName): array
    {
        $githubApi = new GitHubApiService($user);

        if (!$githubApi->hasValidToken()) {
            return [];
        }

        $languageStats = $githubApi->getRepositoryLanguages($repoFullName);
        return collect($languageStats)->sortDesc()->keys()->toArray();
    }
}

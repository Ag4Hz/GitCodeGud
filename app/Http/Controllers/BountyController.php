<?php

namespace App\Http\Controllers;

use App\Http\Requests\BountyStoreRequest;
use App\Http\Requests\BountyUpdateRequest;
use App\Models\Bounty;
use App\Models\Issue;
use App\Models\Repo;
use App\Services\GitHubApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            $issue = Issue::firstOrCreate(
                ['url' => $validated['issue_url'], 'repo_id' => $repo->id],
                ['description' => $validated['description'] ?? '']
            );
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
        $this->authorize('update', $bounty);
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
        $this->authorize('delete', $bounty);
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
        $this->authorize('restore', $bounty);
        $bounty->restore();

        return redirect()
            ->route('profile.show')
            ->with('success', 'Bounty restored successfully!');
    }

    /**
     * Get public bounties for search/popular lists (excludes soft deleted).
     */
    public function index(Request $request)
    {
        $query = Bounty::with(['issue.repo'])
            ->active()
            ->where('status', 'open')
            ->latest();

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhereHas('issue.repo', function ($repo) use ($searchTerm) {
                        $repo->where('git_id', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Language filtering
        if ($request->filled('language')) {
            $language = $request->get('language');
            $query->whereJsonContains('languages', $language);
        }

        $bounties = $query->paginate(12)
            ->withQueryString(); // Preserve query parameters in pagination links

        // Get available languages for filter dropdown
        $availableLanguages = $this->getAvailableLanguages();

        return Inertia::render('bounties/Index', [
            'bounties' => $bounties,
            'availableLanguages' => $availableLanguages,
            'filters' => [
                'search' => $request->get('search', ''),
                'language' => $request->get('language', ''),
            ],
        ]);
    }

    /**
     * Get all unique languages from active bounties for the filter dropdown.
     */
    private function getAvailableLanguages(): array
    {
        $languages = Bounty::active()
            ->where('status', 'open')
            ->whereNotNull('languages')
            ->get()
            ->pluck('languages')
            ->flatten()
            ->unique()
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        return $languages;
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

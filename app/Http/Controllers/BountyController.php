<?php

namespace App\Http\Controllers;

use App\Http\Requests\BountyStoreRequest;
use App\Http\Requests\BountyUpdateRequest;
use App\Models\Bounty;
use App\Models\Issue;
use App\Models\Repo;
use App\Services\BountySearchService;
use App\Services\GitHubApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BountyController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private BountySearchService $bountySearchService
    ) {}

    public function index(Request $request)
    {
        $bountySearchData = $this->bountySearchService->getBountyData($request);

        return Inertia::render('bounties/Index', $bountySearchData);
    }

    public function store(BountyStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $repoInfo = GitHubApiService::parseGitHubUrl($validated['repo_url']);

        $repo = Repo::where('git_id', $repoInfo['full_name'])->firstOrFail();

        $issue = Issue::firstOrCreate(
            ['url' => $validated['issue_url'], 'repo_id' => $repo->id],
            ['description' => $validated['description'] ?? '']
        );

        $user = $request->user();
        $githubApi = new GitHubApiService($user);
        $repoLanguages = $githubApi->hasValidToken()
            ? $githubApi->getRepositoryLanguages($repoInfo['full_name'])
            : [];

        Bounty::create([
            'issue_id' => $issue->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'reward_xp' => $validated['reward_xp'],
            'languages' => collect($repoLanguages)->sortDesc()->keys()->toArray(),
            'status' => 'open',
        ]);

        return redirect()
            ->route('profile.show')
            ->with('success', 'Bounty created successfully!');
    }

    public function show(Bounty $bounty, Request $request): Response
    {
        return Inertia::render('bounties/Show', [
            'bounty' => $bounty->load(['issue.repo.user', 'submissions.user']),
            'comments' => Inertia::merge(fn() => $this->getPaginatedComments($bounty, $request)),
        ]);
    }

    private function getPaginatedComments(Bounty $bounty, Request $request): array
    {
        $user = $request->user();
        if (!$user) return [];

        $githubApi = new GitHubApiService($user);
        if (!$githubApi->hasValidToken() || !$bounty->issue?->url) {
            return [];
        }

        $allComments = $githubApi->getIssueCommentsByUrl($bounty->issue->url);
        if (empty($allComments)) return [];

        $perPage = $request->get('per_page', 10);
        $currentPage = $request->get('page', 1);

        $comments = collect($allComments);

        $paginatedComments = new \Illuminate\Pagination\LengthAwarePaginator(
            $comments->forPage($currentPage, $perPage),
            $comments->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );

        return $paginatedComments->withQueryString()->toArray();
    }

    public function edit(Bounty $bounty): Response
    {
        $this->authorize('update', $bounty);

        return Inertia::render('bounties/Edit', [
            'bounty' => $bounty->load(['issue.repo']),
        ]);
    }

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

    public function destroy(string $id): RedirectResponse
    {
        $bounty = Bounty::findOrFail($id);
        $this->authorize('delete', $bounty);

        $bounty->delete();

        return redirect()
            ->route('profile.show')
            ->with('success', 'Bounty archived successfully! You can restore it from your archived bounties.');
    }

    public function restore(string $id): RedirectResponse
    {
        $bounty = Bounty::withTrashed()->findOrFail($id);
        $this->authorize('restore', $bounty);

        $bounty->restore();

        return redirect()
            ->route('profile.show')
            ->with('success', 'Bounty restored successfully!');
    }
}

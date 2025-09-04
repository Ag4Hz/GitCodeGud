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
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
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

        $repo = Repo::where('git_id', $repoInfo['full_name'])->first();

        if (!$repo) {
            $repo = Repo::create([
                'git_id' => $repoInfo['full_name'],
                'name' => $repoInfo['name'],
                'url' => $validated['repo_url'],
                'user_id' => $request->user()->id,
            ]);
        }

        $issue = Issue::firstOrCreate(
            ['url' => $validated['issue_url'], 'repo_id' => $repo->id],
            ['description' => $validated['description'] ?? '']
        );

        $user = $request->user();
        $githubApi = new GitHubApiService($user);
        $repoLanguages = $githubApi->hasValidToken()
            ? $githubApi->getRepositoryLanguages($repoInfo['full_name'])
            : [];

        $bounty = Bounty::create([
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

    public function submissions(Request $request, Bounty $bounty): Response
    {
        $user = $request->user();

        if (!$user || $bounty->issue->repo->user_id !== $user->id) {
            abort(403, 'Only the bounty owner can view all submissions.');
        }

        return Inertia::render('bounties/Submissions', [
            'bounty' => $bounty->load(['issue.repo']),
            'submissions' => $bounty->submissions()->with(['user'])->latest()->paginate(10),
        ]);
    }

    public function show(Request $request, Bounty $bounty): Response
    {
        $this->trackBountyView($request, $bounty);

        $user = $request->user();
        $userSubmission = null;
        $canUserSubmit = false;

        if ($user) {
            $userSubmission = $bounty->submissions()
                ->where('user_id', $user->id)
                ->first();

            $canUserSubmit = $user->can('create', [\App\Models\Submission::class, $bounty]);
        }

        return Inertia::render('bounties/Show', [
            'bounty' => $bounty->load(['issue.repo', 'submissions.user'])->loadCount('submissions'),
            'popularityScore' => ($bounty->views ?? 0) + ($bountyData->submissions_count ?? 0),
            'comments' => Inertia::merge(fn() => $this->getPaginatedComments($bounty, $request)),
            'canUserSubmit' => $canUserSubmit,
            'userSubmission' => $userSubmission,
            ]);
    }

    private function trackBountyView(Request $request, Bounty $bounty): void
    {
        $user = $request->user();

        if ($user) {
            $cacheKey = "user_{$user->id}_viewed_bounty_{$bounty->id}";

            if (\Illuminate\Support\Facades\Cache::add($cacheKey, true, now()->addDay())) {
                $bounty->increment('views');
            }
        } else {
            $sessionKey = 'viewed_bounties';
            $viewedBounties = session()->get($sessionKey, []);

            if (!in_array($bounty->id, $viewedBounties)) {
                $bounty->increment('views');
                $viewedBounties[] = $bounty->id;
                session()->put($sessionKey, $viewedBounties);
            }
        }
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

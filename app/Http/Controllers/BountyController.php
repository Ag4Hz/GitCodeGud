<?php

namespace App\Http\Controllers;

use App\Http\Requests\BountyStoreRequest;
use App\Http\Requests\BountyUpdateRequest;
use App\Models\Bounty;
use App\Models\Issue;
use App\Models\Repo;
use App\Services\BountySearchService;
use App\Services\GitHubApiService;
use App\Services\GitRepoService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BountyController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private BountySearchService $bountySearchService
    )
    {
    }

    public function index(Request $request)
    {
        $bountySearchData = $this->bountySearchService->getBountyData($request);
        return Inertia::render('bounties/Index', $bountySearchData);
    }

    public function create(Request $request): Response
    {
        $userBounties = Bounty::with(['issue.repo'])
            ->whereHas('issue.repo', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->withTrashed()
            ->latest()
            ->paginate(10);

        $repositories = [];
        $repositoryQuery = $request->input('repository_search', '');
        $providerFilter = $request->input('provider_filter', '');
        if ($repositoryQuery) {
            $repositories = $this->getRepositoryData($request)['repositories'];
        }

        $issues = [];
        $selectedRepo = $request->input('selected_repository', '');
        $selectedProvider = $request->input('selected_provider', 'github');
        if ($selectedRepo) {
            $issues = $this->getIssueData($request, $selectedRepo, $selectedProvider)['issues'];
        }

        $connectedProviders = [];
        if ($request->user()) {
            $repoService = new GitRepoService($request->user());
            $connectedProviders = $repoService->getConnectedProviders();
        }

        return Inertia::render('bounties/CreateBounty', [
            'bounties' => $userBounties,
            'repositories' => $repositories,
            'repositoryQuery' => $repositoryQuery,
            'providerFilter' => $providerFilter,
            'issues' => $issues,
            'selectedRepository' => $selectedRepo,
            'selectedProvider' => $selectedProvider,
            'connectedProviders' => $connectedProviders,
        ]);
    }

    public function store(BountyStoreRequest $request): RedirectResponse
    {
        $validated = $request->getValidatedDataForStore();

        $repoInfo = GitRepoService::parseGitUrl($validated['repo_url']);
        $provider = $repoInfo['provider'] ?? 'github';

        $repo = Repo::where('git_id', $repoInfo['full_name'])
            ->where('provider', $provider)
            ->first();

        if (!$repo) {
            $repo = Repo::create([
                'git_id' => $repoInfo['full_name'],
                'url' => $validated['repo_url'],
                'user_id' => $request->user()->id,
                'provider' => $provider,
            ]);
        }

        preg_match('/\/-\/issues\/(\d+)|\/issues\/(\d+)/', $validated['issue_url'], $matches);
        $issueNumber = $matches[1] ?: ($matches[2] ?? null);

        $issue = Issue::firstOrCreate(
            [
                'url' => $validated['issue_url'],
                'repo_id' => $repo->id,
                'git_id' => $issueNumber,
                'provider' => $provider,
            ],
            [
                'description' => $validated['description'] ?? '',
            ]
        );

        $user = $request->user();
        $repoService = new GitRepoService($user);
        $repoLanguages = $repoService->getRepositoryLanguages($provider, $repoInfo['full_name']);

        $bounty = Bounty::create([
            'issue_id' => $issue->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'reward_xp' => $validated['reward_xp'],
            'languages' => collect($repoLanguages)->sortDesc()->keys()->toArray(),
            'status' => 'open',
        ]);

        return redirect()
            ->route('bounties.create')
            ->with('success', 'Bounty created successfully!')
            ->with('refresh', true);
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
                ->latest()
                ->first();

            $canUserSubmit = $user->can('create', [\App\Models\Submission::class, $bounty]);
        }

        return Inertia::render('bounties/Show', [
            'bounty' => $bounty->load(['issue.repo', 'submissions.user'])->loadCount('submissions'),
            'popularityScore' => ($bounty->views ?? 0) + ($bounty->submissions_count ?? 0),
            'comments' => Inertia::merge(fn() => $this->getPaginatedComments($bounty, $request)),
            'canUserSubmit' => $canUserSubmit,
            'userSubmission' => $userSubmission,
        ]);
    }

    private function trackBountyView(Request $request, Bounty $bounty): void
    {
        $sessionKey = 'bounty_viewed_' . $bounty->id;
        if (!$request->session()->has($sessionKey)) {
            $bounty->increment('views');
            $request->session()->put($sessionKey, true);
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
            ->route('bounties.create')
            ->with('success', 'Bounty updated successfully!');
    }

    public function destroy(string $id): RedirectResponse
    {
        $bounty = Bounty::findOrFail($id);
        $this->authorize('delete', $bounty);

        $bounty->delete();

        return redirect()
            ->route('bounties.create')
            ->with('success', 'Bounty archived successfully! You can restore it from your archived bounties.');
    }

    public function restore(string $id): RedirectResponse
    {
        $bounty = Bounty::withTrashed()->findOrFail($id);
        $this->authorize('restore', $bounty);

        $bounty->restore();

        return redirect()
            ->route('bounties.create')
            ->with('success', 'Bounty restored successfully!');
    }

    public function searchRepositories(Request $request): RedirectResponse
    {
        return redirect()->route('bounties.create', [
            'repository_search' => $request->input('query', ''),
            'provider_filter' => $request->input('provider_filter', ''),
            'page' => $request->input('page', 1)
        ]);
    }

    public function getRepositoryIssues(Request $request, string $owner, string $repo): RedirectResponse
    {
        return redirect()->route('bounties.create', [
            'selected_repository' => $owner . '/' . $repo,
            'selected_provider' => $request->input('provider', 'github'),
            'issue_page' => $request->input('page', 1)
        ]);
    }

    private function getRepositoryData(Request $request): array
    {
        $user = $request->user();
        $query = $request->input('repository_search', '');
        $providerFilter = $request->input('provider_filter', '');
        $page = $request->input('page', 1);
        $perPage = 30;

        $emptyResponse = [
            'repositories' => [],
            'query' => $query,
            'providerFilter' => $providerFilter,
            'total' => 0,
            'page' => $page,
            'hasMore' => false,
        ];

        if (!$user) {
            return $emptyResponse;
        }

        $repoService = new GitRepoService($user);
        $connectedProviders = $repoService->getConnectedProviders();

        if (empty($connectedProviders)) {
            return $emptyResponse;
        }

        $cacheKey = "user_repos_{$user->id}_" . md5(implode('_', $connectedProviders));

        $allRepositories = Cache::remember($cacheKey, 3600, function () use ($repoService) {
            return $repoService->getAllUserRepositories([
                'per_page' => 100,
            ]);
        });

        if (!empty($providerFilter) && $providerFilter !== 'all') {
            $allRepositories = array_filter($allRepositories, fn($repo) =>
                ($repo['provider'] ?? 'github') === $providerFilter
            );
        }

        if (!empty($query)) {
            $allRepositories = array_filter($allRepositories, function ($repo) use ($query) {
                return stripos($repo['name'], $query) !== false ||
                    stripos($repo['full_name'], $query) !== false ||
                    (isset($repo['description']) && stripos($repo['description'], $query) !== false);
            });
        }

        return [
            'repositories' => array_values($allRepositories),
            'query' => $query,
            'providerFilter' => $providerFilter,
            'total' => count($allRepositories),
            'page' => 1,
            'hasMore' => false,
        ];
    }

    private function getIssueData(Request $request, string $repoFullName, string $provider = 'github'): array
    {
        $user = $request->user();
        $page = $request->input('issue_page', 1);
        $perPage = 10;

        $emptyResponse = [
            'issues' => [],
            'repository' => $repoFullName,
            'provider' => $provider,
            'total' => 0,
            'page' => $page,
            'hasMore' => false,
        ];

        if (!$user) {
            return $emptyResponse;
        }

        $repoService = new GitRepoService($user);
        if (!$repoService->hasProvider($provider)) {
            return $emptyResponse;
        }

        $allIssues = $repoService->getRepositoryIssues($provider, $repoFullName, [
            'state' => 'open',
            'per_page' => $perPage,
            'page' => $page,
        ]);

        if ($provider === 'github') {
            $allIssues = array_filter($allIssues, function ($issue) {
                return !isset($issue['pull_request']);
            });
        }

        $issues = array_values($allIssues);

        return [
            'issues' => $issues,
            'repository' => $repoFullName,
            'provider' => $provider,
            'total' => count($issues),
            'page' => $page,
            'hasMore' => count($issues) >= $perPage,
        ];
    }
}

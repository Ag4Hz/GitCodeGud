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
        if ($repositoryQuery) {
            $repositories = $this->getRepositoryData($request)['repositories'];
        }

        $issues = [];
        $selectedRepo = $request->input('selected_repository', '');
        if ($selectedRepo) {
            [$owner, $repo] = explode('/', $selectedRepo);
            $issues = $this->getIssueData($request, $owner, $repo)['issues'];
        }

        return Inertia::render('bounties/CreateBounty', [
            'bounties' => $userBounties,
            'repositories' => $repositories,
            'repositoryQuery' => $repositoryQuery,
            'issues' => $issues,
            'selectedRepository' => $selectedRepo,
        ]);
    }

    public function store(BountyStoreRequest $request): RedirectResponse
    {
        $validated = $request->getValidatedDataForStore();

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
            'page' => $request->input('page', 1)
        ]);
    }

    public function getRepositoryIssues(Request $request, string $owner, string $repo): RedirectResponse
    {
        return redirect()->route('bounties.create', [
            'selected_repository' => $owner . '/' . $repo,
            'issue_page' => $request->input('page', 1)
        ]);
    }

    private function getRepositoryData(Request $request): array
    {
        $user = $request->user();
        $query = $request->input('repository_search', '');
        $page = $request->input('page', 1);
        $perPage = 10;

        $repositories = [];

        if ($user && $user->oauth_provider_token) {
            $githubApi = new GitHubApiService($user);
            $allRepositories = $githubApi->getUserRepositories([
                'type' => 'owner',
                'sort' => 'updated',
                'per_page' => $perPage,
                'page' => $page
            ]);

            if (!empty($query)) {
                $allRepositories = array_filter($allRepositories, function($repo) use ($query) {
                    return stripos($repo['name'], $query) !== false ||
                        stripos($repo['full_name'], $query) !== false ||
                        (isset($repo['description']) && stripos($repo['description'], $query) !== false);
                });
            }

            $repositories = array_map(function($repo) {
                return [
                    'id' => $repo['id'],
                    'name' => $repo['name'],
                    'full_name' => $repo['full_name'],
                    'description' => $repo['description'] ?? '',
                    'url' => $repo['html_url'],
                    'language' => $repo['language'] ?? 'Unknown',
                    'updated_at' => $repo['updated_at'],
                    'open_issues_count' => $repo['open_issues_count'] ?? 0,
                ];
            }, array_values($allRepositories));
        }

        return [
            'repositories' => $repositories,
            'query' => $query,
            'total' => count($repositories),
            'page' => $page,
            'hasMore' => count($repositories) >= $perPage,
        ];
    }

    private function getIssueData(Request $request, string $owner, string $repo): array
    {
        $user = $request->user();
        $repoFullName = $owner . '/' . $repo;
        $page = $request->input('issue_page', 1);
        $perPage = 10;

        $issues = [];

        if ($user && $user->oauth_provider_token) {
            $githubApi = new GitHubApiService($user);

            if (method_exists($githubApi, 'getRepositoryIssues')) {
                $allIssues = $githubApi->getRepositoryIssues($repoFullName, [
                    'state' => 'open',
                    'per_page' => $perPage,
                    'page' => $page,
                    'sort' => 'updated',
                    'direction' => 'desc'
                ]);

                $allIssues = array_filter($allIssues, function($issue) {
                    return !isset($issue['pull_request']);
                });

                $issues = array_map(function($issue) {
                    return [
                        'id' => $issue['id'],
                        'number' => $issue['number'],
                        'title' => $issue['title'],
                        'body' => $issue['body'] ?? '',
                        'url' => $issue['html_url'],
                        'state' => $issue['state'],
                        'created_at' => $issue['created_at'],
                        'updated_at' => $issue['updated_at'],
                        'user' => [
                            'login' => $issue['user']['login'],
                            'avatar_url' => $issue['user']['avatar_url']
                        ],
                        'labels' => array_map(function($label) {
                            return [
                                'name' => $label['name'],
                                'color' => $label['color']
                            ];
                        }, $issue['labels'] ?? []),
                        'comments' => $issue['comments'] ?? 0,
                    ];
                }, array_values($allIssues));
            }
        }

        return [
            'issues' => $issues,
            'repository' => $repoFullName,
            'total' => count($issues),
            'page' => $page,
            'hasMore' => count($issues) >= $perPage,
        ];
    }
}

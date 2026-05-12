<?php

namespace App\Http\Controllers;

use App\Helpers\XPHelper;
use App\Http\Requests\BountyStoreRequest;
use App\Http\Requests\BountyUpdateRequest;
use App\Models\Bounty;
use App\Models\Issue;
use App\Models\Repo;
use App\Services\BountySearchService;
use App\Services\GitProviderFactory;
use App\Services\GitRepoService;
use App\Services\JiraApiService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
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
        $providerFilter = $request->input('provider_filter', '');
        if ($repositoryQuery) {
            $repositories = $this->getRepositoryData($request)['repositories'];
        }

        $issues = [];
        $selectedRepo = $request->input('selected_repository', '');
        $selectedProvider = $request->input('selected_provider', 'github');
        if ($selectedRepo && $selectedProvider !== 'bitbucket') {
            $issues = $this->getIssueData($request, $selectedRepo, $selectedProvider)['issues'];
        }

        $connectedProviders = [];
        if ($request->user()) {
            $repoService = new GitRepoService($request->user());
            $connectedProviders = $repoService->getConnectedProviders();

            $hasJira = $request->user()->providers()->where('provider', 'jira')->exists();
            if ($hasJira && !in_array('jira', $connectedProviders)) {
                $connectedProviders[] = 'jira';
            }
        }

        $ownedOrganizations = $request->user()
            ->organizations()
            ->select('organizations.id', 'organizations.name', 'organizations.github_repo', 'organizations.gitlab_repo', 'organizations.bitbucket_repo')
            ->get();

        return Inertia::render('bounties/CreateBounty', [
            'bounties'            => $userBounties,
            'repositories'        => $repositories,
            'repositoryQuery'     => $repositoryQuery,
            'providerFilter'      => $providerFilter,
            'issues'              => $issues,
            'selectedRepository'  => $selectedRepo,
            'selectedProvider'    => $selectedProvider,
            'connectedProviders'  => $connectedProviders,
            'ownedOrganizations'  => $ownedOrganizations,
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
                'git_id'   => $repoInfo['full_name'],
                'url'      => $validated['repo_url'],
                'user_id'  => $request->user()->id,
                'provider' => $provider,
            ]);
        }

        if ($provider === 'bitbucket') {
            $jiraParsed  = JiraApiService::parseIssueUrl($validated['issue_url']);
            $issueNumber = $jiraParsed['issue_key'] ?? null;
            $issueProvider = 'jira';
        } else {
            preg_match('/\/-\/issues\/(\d+)|\/issues\/(\d+)/', $validated['issue_url'], $matches);
            $issueNumber   = $matches[1] ?: ($matches[2] ?? null);
            $issueProvider = $provider;
        }

        $issue = Issue::firstOrCreate(
            [
                'url'     => $validated['issue_url'],
                'repo_id' => $repo->id,
            ],
            [
                'git_id'      => $issueNumber,
                'provider'    => $issueProvider,
                'description' => $validated['description'] ?? '',
            ]
        );

        $user = $request->user();

        if (!XPHelper::canAffordBounty($user, $validated['reward_xp'])) {
            return redirect()->back()
                ->withErrors(['reward_xp' => 'Nincs elég XP-d ehhez a bountyhoz. Jelenlegi egyenleged: ' . $user->xp . ' XP.'])
                ->withInput();
        }

        XPHelper::deductBountyXP($user, $validated['reward_xp']);

        $repoService = new GitRepoService($user);
        $repoLanguages = $repoService->getRepositoryLanguages($provider, $repoInfo['full_name']);

        $bounty = Bounty::create([
            'issue_id'        => $issue->id,
            'organization_id' => $validated['organization_id'] ?? null,
            'title'           => $validated['title'],
            'description'     => $validated['description'] ?? '',
            'reward_xp'       => $validated['reward_xp'],
            'languages'       => collect($repoLanguages)->sortDesc()->keys()->toArray(),
            'status'          => 'open',
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
            'bounty'        => $bounty->load(['issue.repo']),
            'submissions'   => $bounty->submissions()->with(['user'])->latest()->paginate(10),
            'acceptedCount' => $bounty->submissions()->where('status', 'accepted')->count(),
        ]);
    }

    public function show(Request $request, Bounty $bounty): Response
    {
        $this->authorize('view', $bounty);
        $this->trackBountyView($request, $bounty);

        $user          = $request->user();
        $userSubmission = null;
        $canUserSubmit  = false;

        if ($user) {
            $userSubmission = $bounty->submissions()
                ->where('user_id', $user->id)
                ->latest()
                ->first();

            $canUserSubmit = $user->can('create', [\App\Models\Submission::class, $bounty]);
        }

        return Inertia::render('bounties/Show', [
            'bounty'          => $bounty->load(['issue.repo', 'submissions.user'])->loadCount('submissions'),
            'popularityScore' => ($bounty->views ?? 0) + ($bounty->submissions_count ?? 0),
            'comments'        => Inertia::merge(fn() => $this->getPaginatedComments($bounty, $request)),
            'canUserSubmit'   => $canUserSubmit,
            'userSubmission'  => $userSubmission,
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
        if (!$user || !$bounty->issue?->url) {
            return [];
        }

        $issueProvider = $bounty->issue->provider ?? 'github';

        if ($issueProvider === 'jira') {
            $jiraProvider = $user->providers()->where('provider', 'jira')->first();
            if (!$jiraProvider || !$jiraProvider->token) {
                $jiraProvider = $bounty->issue->repo->user?->providers()
                    ->where('provider', 'jira')
                    ->first();
            }
            if (!$jiraProvider || !$jiraProvider->token) {
                return [];
            }

            $jiraApi     = JiraApiService::fromProvider($jiraProvider);
            $allComments = $jiraApi->getIssueCommentsByUrl($bounty->issue->url);
            if (empty($allComments)) {
                return [];
            }

            $normalizedComments = $this->normalizeComments($allComments, 'jira');

            $perPage     = $request->get('per_page', 10);
            $currentPage = $request->get('page', 1);
            $comments    = collect($normalizedComments);

            $paginatedComments = new \Illuminate\Pagination\LengthAwarePaginator(
                $comments->forPage($currentPage, $perPage),
                $comments->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'pageName' => 'page']
            );

            return $paginatedComments->withQueryString()->toArray();
        }

        $repoProvider = $bounty->issue->repo->provider ?? $issueProvider;

        $userProvider = $user->providers()->where('provider', $repoProvider)->first();
        if (!$userProvider || !$userProvider->token) {
            return [];
        }

        $apiService = GitProviderFactory::getProvider($repoProvider, $userProvider);

        $allComments = $apiService->getIssueCommentsByUrl($bounty->issue->url);
        if (empty($allComments)) {
            return [];
        }

        $normalizedComments = $this->normalizeComments($allComments, $repoProvider);

        $perPage     = $request->get('per_page', 10);
        $currentPage = $request->get('page', 1);
        $comments    = collect($normalizedComments);

        $paginatedComments = new \Illuminate\Pagination\LengthAwarePaginator(
            $comments->forPage($currentPage, $perPage),
            $comments->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'pageName' => 'page']
        );

        return $paginatedComments->withQueryString()->toArray();
    }

    private function normalizeComments(array $comments, string $provider): array
    {
        return array_map(function ($comment) use ($provider) {
            switch ($provider) {
                case 'gitlab':
                    $body = $comment['body'] ?? '';
                    $body = preg_replace('/<code[^>]*>(.*?)<\/code>/is', '`$1`', $body);
                    $body = preg_replace('/<p[^>]*>/i', '', $body);
                    $body = str_replace('</p>', "\n\n", $body);
                    $body = preg_replace('/<br\s*\/?>/i', "\n", $body);
                    $body = strip_tags($body);
                    $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $body = preg_replace('/\n{3,}/', "\n\n", $body);
                    $body = trim($body);

                    return [
                        'id'         => $comment['id'] ?? null,
                        'body'       => $body,
                        'created_at' => $comment['created_at'] ?? null,
                        'updated_at' => $comment['updated_at'] ?? null,
                        'html_url'   => $comment['web_url'] ?? '#',
                        'user'       => [
                            'login'      => $comment['author']['username'] ?? 'Unknown',
                            'avatar_url' => $comment['author']['avatar_url'] ?? '',
                        ],
                        'reactions'  => ['total_count' => 0],
                    ];

                case 'jira':
                    $body = '';
                    $content = $comment['body']['content'] ?? [];
                    foreach ($content as $block) {
                        foreach ($block['content'] ?? [] as $inline) {
                            if (($inline['type'] ?? '') === 'text') {
                                $body .= $inline['text'] ?? '';
                            }
                        }
                        $body .= "\n";
                    }
                    $body = trim($body);

                    return [
                        'id'         => $comment['id'] ?? null,
                        'body'       => $body,
                        'created_at' => $comment['created'] ?? null,
                        'updated_at' => $comment['updated'] ?? null,
                        'html_url'   => '#',
                        'user'       => [
                            'login'      => $comment['author']['displayName'] ?? 'Unknown',
                            'avatar_url' => $comment['author']['avatarUrls']['48x48'] ?? '',
                        ],
                        'reactions'  => ['total_count' => 0],
                    ];

                case 'bitbucket':
                    return [
                        'id'         => $comment['id'] ?? null,
                        'body'       => $comment['content']['raw'] ?? ($comment['content']['markup'] ?? ''),
                        'created_at' => $comment['created_on'] ?? null,
                        'updated_at' => $comment['updated_on'] ?? null,
                        'html_url'   => $comment['links']['html']['href'] ?? '#',
                        'user'       => [
                            'login'      => $comment['user']['nickname'] ?? ($comment['user']['display_name'] ?? 'Unknown'),
                            'avatar_url' => $comment['user']['links']['avatar']['href'] ?? '',
                        ],
                        'reactions'  => ['total_count' => 0],
                    ];

                case 'github':
                default:
                    return $comment;
            }
        }, $comments);
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
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'reward_xp'   => $validated['reward_xp'],
            'status'      => $validated['status'] ?? $bounty->status,
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

    public function updateStatus(Request $request, Bounty $bounty): RedirectResponse
    {
        $this->authorize('update', $bounty);
        $validated = $request->validate(['status' => ['required', 'in:open,closed']]);
        $bounty->update(['status' => $validated['status']]);

        return back()->with('success', 'Bounty status updated.');
    }

    public function searchRepositories(Request $request): RedirectResponse
    {
        return redirect()->route('bounties.create', [
            'repository_search' => $request->input('query', ''),
            'provider_filter'   => $request->input('provider_filter', ''),
            'page'              => $request->input('page', 1),
        ]);
    }

    public function getRepositoryIssues(Request $request, string $owner, string $repo): RedirectResponse
    {
        return redirect()->route('bounties.create', [
            'selected_repository' => $owner . '/' . $repo,
            'selected_provider'   => $request->input('provider', 'github'),
            'issue_page'          => $request->input('page', 1),
        ]);
    }

    private function getRepositoryData(Request $request): array
    {
        $user           = $request->user();
        $query          = $request->input('repository_search', '');
        $providerFilter = $request->input('provider_filter', '');
        $page           = $request->input('page', 1);

        $emptyResponse = [
            'repositories'  => [],
            'query'         => $query,
            'providerFilter' => $providerFilter,
            'total'         => 0,
            'page'          => $page,
            'hasMore'       => false,
        ];

        if (!$user) {
            return $emptyResponse;
        }

        $repoService        = new GitRepoService($user);
        $connectedProviders = $repoService->getConnectedProviders();

        if (empty($connectedProviders)) {
            return $emptyResponse;
        }

        $cacheKey = "user_repos_{$user->id}_" . md5(implode('_', $connectedProviders));

        $allRepositories = Cache::remember($cacheKey, 3600, function () use ($repoService) {
            return $repoService->getAllUserRepositories(['per_page' => 100]);
        });

        if (!empty($providerFilter) && $providerFilter !== 'all') {
            $allRepositories = array_filter(
                $allRepositories,
                fn($repo) => ($repo['provider'] ?? 'github') === $providerFilter
            );
        }

        if (!empty($query)) {
            $allRepositories = array_filter($allRepositories, function ($repo) use ($query) {
                return stripos($repo['name'], $query) !== false
                    || stripos($repo['full_name'], $query) !== false
                    || (isset($repo['description']) && stripos($repo['description'], $query) !== false);
            });
        }

        return [
            'repositories'  => array_values($allRepositories),
            'query'         => $query,
            'providerFilter' => $providerFilter,
            'total'         => count($allRepositories),
            'page'          => 1,
            'hasMore'       => false,
        ];
    }

    private function getIssueData(Request $request, string $repoFullName, string $provider = 'github'): array
    {
        $user    = $request->user();
        $page    = $request->input('issue_page', 1);
        $perPage = 10;

        $emptyResponse = [
            'issues'     => [],
            'repository' => $repoFullName,
            'provider'   => $provider,
            'total'      => 0,
            'page'       => $page,
            'hasMore'    => false,
        ];

        if (!$user) {
            return $emptyResponse;
        }

        $repoService = new GitRepoService($user);
        if (!$repoService->hasProvider($provider)) {
            return $emptyResponse;
        }

        $allIssues = $repoService->getRepositoryIssues($provider, $repoFullName, [
            'state'    => 'open',
            'per_page' => $perPage,
            'page'     => $page,
        ]);

        if ($provider === 'github') {
            $allIssues = array_filter($allIssues, fn($issue) => !isset($issue['pull_request']));
        }

        $issues = array_values($allIssues);

        return [
            'issues'     => $issues,
            'repository' => $repoFullName,
            'provider'   => $provider,
            'total'      => count($issues),
            'page'       => $page,
            'hasMore'    => count($issues) >= $perPage,
        ];
    }
}

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

    public function show(Request $request, Bounty $bounty): Response
    {
        $this->trackBountyView($request, $bounty);

        $bountyData = $bounty->load(['issue.repo', 'submissions.user'])
            ->loadCount('submissions');

        return Inertia::render('bounties/Show', [
            'bounty' => $bountyData,
            'popularityScore' => ($bounty->views ?? 0) + ($bountyData->submissions_count ?? 0),
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

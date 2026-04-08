<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class OrganizationController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'slug'           => ['required', 'string', 'max:255', 'unique:organizations,slug'],
            'github_repo'    => ['nullable', 'string', 'max:255'],
            'gitlab_repo'    => ['nullable', 'string', 'max:255'],
            'bitbucket_repo' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($validated['github_repo']) && empty($validated['gitlab_repo']) && empty($validated['bitbucket_repo'])) {
            return back()->withErrors(['repo' => 'At least one repository must be provided (GitHub, GitLab, or Bitbucket).'])->withInput();
        }

        $user = $request->user();

        if (!empty($validated['github_repo'])) {
            if (Organization::where('github_repo', $validated['github_repo'])->exists()) {
                return back()->withErrors(['github_repo' => 'An organization already exists for this GitHub repository.'])->withInput();
            }
            $githubProvider = $user->providers()->where('provider', 'github')->first();
            if (!$githubProvider) {
                return back()->withErrors(['github_repo' => 'You must connect your GitHub account first.'])->withInput();
            }
            $githubApi = new \App\Services\GitHubApiService($githubProvider);
            try {
                $githubApi->getRepository($validated['github_repo']);
            } catch (\Exception $e) {
                return back()->withErrors(['github_repo' => 'GitHub repository not found or not accessible.'])->withInput();
            }
        }

        if (!empty($validated['gitlab_repo'])) {
            if (Organization::where('gitlab_repo', $validated['gitlab_repo'])->exists()) {
                return back()->withErrors(['gitlab_repo' => 'An organization already exists for this GitLab repository.'])->withInput();
            }
            $gitlabProvider = $user->providers()->where('provider', 'gitlab')->first();
            if (!$gitlabProvider) {
                return back()->withErrors(['gitlab_repo' => 'You must connect your GitLab account first.'])->withInput();
            }
            $gitlabApi = new \App\Services\GitLabApiService($gitlabProvider);
            try {
                $gitlabApi->getRepository($validated['gitlab_repo']);
            } catch (\Exception $e) {
                return back()->withErrors(['gitlab_repo' => 'GitLab repository not found or not accessible.'])->withInput();
            }
        }

        if (!empty($validated['bitbucket_repo'])) {
            if (Organization::where('bitbucket_repo', $validated['bitbucket_repo'])->exists()) {
                return back()->withErrors(['bitbucket_repo' => 'An organization already exists for this Bitbucket repository.'])->withInput();
            }
            $bitbucketProvider = $user->providers()->where('provider', 'bitbucket')->first();
            if (!$bitbucketProvider) {
                return back()->withErrors(['bitbucket_repo' => 'You must connect your Bitbucket account first.'])->withInput();
            }
            $bitbucketApi = new \App\Services\BitbucketApiService($bitbucketProvider);
            try {
                $bitbucketApi->getRepository($validated['bitbucket_repo']);
            } catch (\Exception $e) {
                return back()->withErrors(['bitbucket_repo' => 'Bitbucket repository not found or not accessible.'])->withInput();
            }
        }

        $organization = Organization::create(array_merge($validated, [
            'owner_id' => $user->id,
        ]));

        $organization->members()->attach($user->id, [
            'role'      => 'owner',
            'joined_at' => now(),
        ]);

        return redirect()->route('organizations.show', $organization);
    }

    public function show(Request $request, Organization $organization): Response
    {
        $this->authorize('view', $organization);
        $members = $organization->members()
            ->withPivot('role', 'joined_at')
            ->get();
        return Inertia::render('Organizations/Show', [
            'organization' => $organization->load('owner'),
            'members'      => $members,
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('update', $organization);
        $validated = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'slug'           => ['required', 'string', 'max:255', 'unique:organizations,slug,' . $organization->id],
            'github_repo'    => ['nullable', 'string', 'max:255'],
            'gitlab_repo'    => ['nullable', 'string', 'max:255'],
            'bitbucket_repo' => ['nullable', 'string', 'max:255'],
        ]);
        $organization->update($validated);
        return redirect()->route('organizations.show', $organization);
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);
        $organization->members()->detach();
        $organization->delete();
        return redirect()->route('dashboard');
    }

    public function members(Organization $organization): Response
    {
        $this->authorize('view', $organization);
        $members = $organization->members()
            ->withPivot('role', 'joined_at')
            ->get();
        return Inertia::render('Organizations/Members', [
            'organization' => $organization,
            'members'      => $members,
        ]);
    }

    public function removeMember(Organization $organization, User $user): RedirectResponse
    {
        $this->authorize('update', $organization);
        if ($organization->owner_id === $user->id) {
            return back()->withErrors(['member' => 'Cannot remove the owner.']);
        }
        $organization->members()->detach($user->id);
        Mail::raw(
            "You have been removed from the organization \"{$organization->name}\" on GitCodeGud.",
            fn($msg) => $msg->to($user->email)->subject("Removed from {$organization->name}")
        );
        return back()->with('success', 'Member removed.');
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $organizations = $user->organizations()
            ->withPivot('role', 'joined_at')
            ->withCount('members')
            ->get();

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations,
        ]);
    }
}

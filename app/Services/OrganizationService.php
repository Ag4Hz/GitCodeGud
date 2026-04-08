<?php

namespace App\Services;

use App\Mail\OrganizationInviteMail;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\User;
use App\Services\GitHubApiService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizationService
{
    public function invite(Organization $organization, string $email): void
    {
        $alreadyMember = $organization->members()
            ->where('email', $email)
            ->exists();
        if ($alreadyMember) {
            throw ValidationException::withMessages([
                'email' => 'This user is already a member of the organization.',
            ]);
        }
        $pendingInvite = OrganizationInvite::where('organization_id', $organization->id)
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists();
        if ($pendingInvite) {
            throw ValidationException::withMessages([
                'email' => 'An invitation has already been sent to this email.',
            ]);
        }

        $token = (string) Str::uuid();

        OrganizationInvite::create([
            'organization_id' => $organization->id,
            'email'           => $email,
            'token'           => $token,
            'expires_at'      => now()->addDays(7),
        ]);

        $acceptUrl  = route('organizations.invite.accept', [
            'organization' => $organization->id,
            'token'        => $token,
        ]);

        $declineUrl = route('organizations.invite.decline', [
            'organization' => $organization->id,
            'token'        => $token,
        ]);

        Mail::to($email)->send(new OrganizationInviteMail($organization, $acceptUrl, $declineUrl));
    }

    public function acceptInvite(string $token, User $user): void
    {
        $invite = OrganizationInvite::where('token', $token)->firstOrFail();

        if ($invite->isExpired()) {
            throw ValidationException::withMessages(['token' => 'This invitation has expired.']);
        }

        if ($invite->isAccepted()) {
            throw ValidationException::withMessages(['token' => 'This invitation has already been accepted.']);
        }

        $invite->organization->members()->attach($user->id, [
            'role'      => 'member',
            'joined_at' => now(),
        ]);

        $invite->update(['accepted_at' => now()]);

        $organization = $invite->organization;
        if ($organization->github_repo) {
            $owner = $organization->owner;
            $githubProvider = $owner->providers()->where('provider', 'github')->first();

            if ($githubProvider && $githubProvider->token) {
                $githubApi = new GitHubApiService($githubProvider);
                $userGithubProvider = $user->providers()->where('provider', 'github')->first();

                if ($userGithubProvider && $userGithubProvider->provider_id) {
                    $githubUsername = $githubApi->getUserById($userGithubProvider->provider_id);
                    if ($githubUsername) {
                        $githubApi->addCollaborator($organization->github_repo, $githubUsername);
                    }
                }
            }
        }

        if ($organization->gitlab_repo) {
            $owner = $organization->owner;
            $gitlabProvider = $owner->providers()->where('provider', 'gitlab')->first();

            if ($gitlabProvider && $gitlabProvider->token) {
                $gitlabApi = new GitLabApiService($gitlabProvider);
                $userGitlabProvider = $user->providers()->where('provider', 'gitlab')->first();

                if ($userGitlabProvider && $userGitlabProvider->provider_id) {
                    $gitlabApi->addMember($organization->gitlab_repo, $userGitlabProvider->provider_id);
                }
            }
        }

        if ($organization->bitbucket_repo) {
            $owner = $organization->owner;
            Mail::raw(
                "A new member ({$user->name} - {$user->email}) has joined your organization \"{$organization->name}\" on GitCodeGud.\n\n" .
                "Please manually grant them access to your Bitbucket repository: {$organization->bitbucket_repo}\n" .
                "You can do this at: https://bitbucket.org/{$organization->bitbucket_repo}/admin/access",
                fn($msg) => $msg->to($owner->email)->subject("Action required: Grant Bitbucket access to new member")
            );
        }
    }

    public function declineInvite(string $token): void
    {
        $invite = OrganizationInvite::where('token', $token)->firstOrFail();

        $invite->delete();
    }
}

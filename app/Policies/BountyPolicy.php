<?php

namespace App\Policies;

use App\Models\Bounty;
use App\Models\User;
use App\Services\GitHubApiService;

class BountyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Bounty $bounty): bool
    {
        if ($bounty->isDeleted()) {
            return $this->isOwner($user, $bounty);
        }
        return true;
    }

    public function create(User $user): bool
    {
        return auth()->check();
    }

    public function createForRepository(User $user, string $repoUrl): bool
    {
        $githubApi = new GitHubApiService($user);

        if (!$githubApi->hasValidToken()) {
            return false;
        }

        $repoInfo = GitHubApiService::parseGitHubUrl($repoUrl);
        if (!$repoInfo) {
            return false;
        }

        try {
            $gitId = $repoInfo['owner'] . '/' . $repoInfo['name'];
            $repoData = $githubApi->getRepository($gitId);

            return isset($repoData['permissions']) &&
                ($repoData['permissions']['admin'] || $repoData['permissions']['push']);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update(User $user, Bounty $bounty): bool
    {
        if ($bounty->isDeleted()) {
            return false;
        }
        return $this->isOwner($user, $bounty);
    }

    public function delete(User $user, Bounty $bounty): bool
    {
        if ($bounty->isDeleted()) {
            return false;
        }
        return $this->isOwner($user, $bounty);
    }

    public function restore(User $user, Bounty $bounty): bool
    {
        if (!$bounty->isDeleted()) {
            return false;
        }
        return $this->isOwner($user, $bounty);
    }
    /**
     * Check if user can submit to a bounty.
     */
    public function submit(User $user, Bounty $bounty): bool
    {
        if ($bounty->isDeleted()) {
            return false;
        }

        if ($bounty->status === 'closed') {
            return false;
        }

        if ($this->isOwner($user, $bounty)) {
            return false;
        }

        return auth()->check();
    }

    /**
     * Helper method to check if user is the owner of the bounty's repository.
     */
    private function isOwner(User $user, Bounty $bounty): bool
    {
        return $bounty->issue &&
            $bounty->issue->repo &&
            $bounty->issue->repo->user_id === $user->id;
    }
    /**
     * Check if user can export bounty data.
     */
    public function export(User $user, Bounty $bounty): bool
    {
        return $this->isOwner($user, $bounty);
    }
}

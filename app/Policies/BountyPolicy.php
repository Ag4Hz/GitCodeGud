<?php

namespace App\Policies;

use App\Models\Bounty;
use App\Models\User;
use App\Services\GitRepoService;

class BountyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(?User $user, Bounty $bounty): bool
    {
        if ($bounty->trashed()) {
            return $user && $this->isOwner($user, $bounty);
        }

        if ($bounty->organization_id !== null) {
            if (!$user) {
                return false;
            }
            return $bounty->organization->members()->whereKey($user->id)->exists()
                || $bounty->organization->owner_id === $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return auth()->check();
    }

    public function createForRepository(User $user, string $repoUrl, string $provider = 'github'): bool
    {
        $repoService = new GitRepoService($user);

        if (!$repoService->hasProvider($provider)) {
            return false;
        }

        $repoInfo = GitRepoService::parseGitUrl($repoUrl);
        if (!$repoInfo) {
            return false;
        }

        $gitId = $repoInfo['owner'] . '/' . $repoInfo['name'];

        return $repoService->canUserWriteToRepository($provider, $gitId);
    }
    public function update(User $user, Bounty $bounty): bool
    {
        if ($bounty->trashed()) {
            return false;
        }
        return $this->isOwner($user, $bounty);
    }

    public function delete(User $user, Bounty $bounty): bool
    {
        if ($bounty->trashed()) {
            return false;
        }
        return $this->isOwner($user, $bounty);
    }

    public function restore(User $user, Bounty $bounty): bool
    {
        if (!$bounty->trashed()) {
            return false;
        }
        return $this->isOwner($user, $bounty);
    }

    /**
     * Check if user can submit to a bounty.
     */
    public function submit(User $user, Bounty $bounty): bool
    {
        if ($bounty->trashed() || $bounty->status === 'closed' || $this->isOwner($user, $bounty)) {
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

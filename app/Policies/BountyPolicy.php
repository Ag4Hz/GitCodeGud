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
        return $bounty->issue && $bounty->issue->repo && $bounty->issue->repo->user_id === $user->id;
    }

    public function delete(User $user, Bounty $bounty): bool
    {
        return $bounty->issue && $bounty->issue->repo && $bounty->issue->repo->user_id === $user->id;
    }

    public function restore(User $user, Bounty $bounty): bool
    {
        return $this->delete($user, $bounty);
    }

    public function forceDelete(User $user, Bounty $bounty): bool
    {
        return $this->delete($user, $bounty);
    }

}

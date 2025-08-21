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

        $repoInfo = $this->parseGitHubUrl($repoUrl);
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

    /**
     * Parse GitHub repository URL to extract owner and repository name.
     */
    private function parseGitHubUrl(string $url): ?array
    {
        $url = trim($url);
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        $patterns = [
            '/^https?:\/\/github\.com\/([^\/\s]+)\/([^\/\s]+)(?:\.git)?(?:\/.*)?$/i',
            '/github\.com\/([^\/\s]+)\/([^\/\s]+)(?:\.git)?(?:\/.*)?$/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return [
                    'owner' => trim($matches[1]),
                    'name' => trim(rtrim($matches[2], '.git')),
                ];
            }
        }
        return null;
    }
}

<?php

namespace App\Services;

use App\Models\User;
use App\Models\Skill;
use App\Models\SkillUser;
use Illuminate\Support\Facades\Http;

class GitHubSkillSyncService
{
    public function syncUserSkillsFromGitHub(User $user): bool
    {
        try {
            $repositories = $this->getUserRepositories($user);
            if (empty($repositories)) {
                return false;
            }

            $languageStats = $this->getLanguageStatsFromRepos($user, $repositories);
            $this->updateUserSkills($user, $languageStats);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getUserRepositories(User $user): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $user->oauth_provider_token,
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'GitCodeGud-App'
        ])->get('https://api.github.com/user/repos', [
            'type' => 'owner',
            'sort' => 'updated',
            'per_page' => 100
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to fetch GitHub repositories: ' . $response->status());
        }

        return $response->json();
    }

    private function getLanguageStatsFromRepos(User $user, array $repositories): array
    {
        $totalLanguageBytes = [];

        foreach ($repositories as $repo) {
            if (!empty($repo['fork']) || !empty($repo['archived'])) {
                continue;
            }

            $languages = $this->getRepositoryLanguages($user, $repo['full_name']);

            foreach ($languages as $language => $bytes) {
                $totalLanguageBytes[$language] = ($totalLanguageBytes[$language] ?? 0) + $bytes;
            }
        }

        return $totalLanguageBytes;
    }

    private function getRepositoryLanguages(User $user, string $repoFullName): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $user->oauth_provider_token,
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => 'GitCodeGud-App'
        ])->get("https://api.github.com/repos/{$repoFullName}/languages");

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    private function updateUserSkills(User $user, array $languageStats): void
    {
        foreach ($languageStats as $language => $bytes) {
            $skill = Skill::firstOrCreate(['skill_name' => $language]);
            SkillUser::firstOrCreate(
                ['user_id' => $user->id, 'skill_id' => $skill->id],
                ['xp' => 1, 'level' => 1]
            );
        }
    }
}

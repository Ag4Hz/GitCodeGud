<?php
namespace App\Services;

use App\Models\User;

class FollowStatsService
{
    public function attachCounts(User $user): User
    {
        return $user->loadCount(['followers', 'followings']);
    }

    public function counts(User $user): array
    {
        $this->attachCounts($user);

        return [
            'followers_count'  => $user->followers_count,
            'followings_count' => $user->followings_count,
        ];
    }
}

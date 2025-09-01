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
            'followers_count' => $user->followers_count,
            'followings_count' => $user->followings_count,
        ];
    }

    public function getFollowers(User $user)
    {
        return $user->followers()->
        paginate(5)->
        through(fn($user) => [
            'id' => $user->id,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'name' => $user->name
        ]);
    }

    public function getFollowings(User $user)
    {
        return $user->followings()->
        paginate(5)->
        through(fn($user) => [
            'id' => $user->id,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'name' => $user->name
        ]);
    }
}

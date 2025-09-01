<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function follow(User $authUser, User $targetUser): bool
    {
        return $authUser->isNot($targetUser);
    }

    public function unfollow(User $authUser, User $targetUser): bool
    {
        return $authUser->isNot($targetUser);
    }

}

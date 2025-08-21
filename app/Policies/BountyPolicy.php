<?php
namespace App\Policies;

use App\Models\Bounty;
use App\Models\User;
class BountyPolicy
{
    public function viewAny(User $user):bool
    {
        return true;
    }
    public function view(User $user,Bounty $bounty):bool
    {
        return true;
    }
    public function create(User $user):bool
    {
        return true;
    }
    public function update(User $user,Bounty $bounty):bool
    {
        return $bounty->issue && $bounty->issue->repo && $bounty->issue->repo->user_id === $user->id;
    }
    public function delete(User $user,Bounty $bounty):bool
    {
        return $bounty->issue && $bounty->issue->repo && $bounty->issue->repo->user_id === $user->id;
    }
    public function restore(User $user, Bounty $bounty): bool
    {
        return $this->delete($user, $bounty);
    }
    public function forceDelete(User $user,Bounty $bounty):bool
    {
        return $this->delete($user, $bounty);
    }
}

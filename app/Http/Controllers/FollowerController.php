<?php
namespace App\Http\Controllers;

use App\Models\User;

class FollowerController extends Controller
{
    public function store(User $user)
    {
        auth()->user()->followings()->syncWithoutDetaching([$user->id]);
    }

    public function destroy(User $user)
    {
        auth()->user()->followings()->detach($user->id);
    }
}

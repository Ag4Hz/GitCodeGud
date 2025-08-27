<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class FollowerController extends Controller
{
    public function store(User $user)
    {
        Gate::authorize('follow', $user);
        auth()->user()->followings()->syncWithoutDetaching([$user->id]);
    }

    public function destroy(User $user)
    {
        Gate::authorize('unfollow', $user);
        auth()->user()->followings()->detach($user->id);
    }
}

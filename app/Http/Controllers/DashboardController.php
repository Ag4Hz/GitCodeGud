<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return inertia('Dashboard');
    }

    public function searchUsers(Request $request)
    {
        return User::query()
            ->when($request->search, fn ($q) =>
            $q->where('nickname', 'like', "%{$request->search}%")
            )
            ->paginate(30)
            ->withQueryString()
            ->through(fn ($user) => [
                'id'       => $user->id,
                'nickname' => $user->nickname,
                'avatar'   => $user->avatar,
                'name'     => $user->name,
            ]);
    }

}

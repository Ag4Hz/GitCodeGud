<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Pagination\Paginator;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    public function index()
    {
        $perPage = 10;
        $page = Paginator::resolveCurrentPage() ?: 1;
        $start = ($page - 1) * $perPage;

        $users = User::query()
            ->select('id', 'name', 'xp')
            ->orderByDesc('xp')
            ->paginate($perPage)
            ->through(function ($u, $i) use ($start) {
                $u->rank = $start + $i + 1;
                return $u;
            });

        return Inertia::render('Leaderboard', [
            'users' => $users,
        ]);
    }
}

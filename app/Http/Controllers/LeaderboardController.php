<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->select('id', 'name', 'xp')
            ->orderByDesc('xp')
            ->get()
            ->values()
            ->map(function ($u, $i) {
                $u->rank = $i + 1;
                return $u;
            });

        return Inertia::render('Leaderboard', [
            'users' => $users,
        ]);
    }
}

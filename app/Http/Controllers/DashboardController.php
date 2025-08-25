<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $term = $request->string('search')->toString();

        $query = User::query()
            ->when($term !== '', function ($query) use ($term) {
                $query->where('nickname', 'like', "%{$term}%");
            });

        return Inertia::render('Dashboard', [
            'filters' => [
                'search' => $term,
            ],
            'results' => Inertia::defer(fn () =>
            $query->orderBy('nickname')
                ->paginate(30)
                ->withQueryString()
                ->through(fn ($user) => [
                    'id'       => $user->id,
                    'nickname' => $user->nickname,
                    'avatar'   => $user->avatar,
                    'name'     => $user->name,
                ])
            ),
        ]);
    }
}

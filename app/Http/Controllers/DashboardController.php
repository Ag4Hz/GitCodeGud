<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $search = (string) $request->input('q', '');

        $users = User::query()
            ->select(['id', 'name', 'nickname'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->Where('nickname', 'like', "%{$search}%");
                });
            })
            ->orderBy('nickname')
            ->limit(20)
            ->get();

        return Inertia::render('Dashboard', [
            'users'   => $users,
            'filters' => ['q' => $search],
        ]);
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $users = User::select(['id', 'name', 'nickname'])
            ->orderBy('nickname')
            ->paginate(10);

        return Inertia::render('Profile', [
            'users' => $users,
        ]);
    }
}

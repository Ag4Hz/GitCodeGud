<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('search')) {
            return app(UserController::class)->index($request);
        }

        return Inertia::render('Dashboard');
    }
}

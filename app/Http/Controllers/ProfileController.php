<?php

namespace App\Http\Controllers;

use App\Helpers\XPHelper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();

        // Use centralized XP helper for consistency
        $userWithXP = XPHelper::getUserWithXP($user);

        return Inertia::render('Profile', [
            'user' => $userWithXP,
        ]);
    }
}

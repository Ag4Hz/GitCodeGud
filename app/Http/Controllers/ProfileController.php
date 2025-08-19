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
        return Inertia::render('Profile', [
            'user' => XPHelper::getUserWithXP($request->user()),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\XPHelper;
use App\Http\Resources\BountyResource;
use App\Models\User;
use App\Services\UserBountyService as UBS;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private UBS $userBountyService
    ) {}

    public function show(Request $request, ?User $user = null): Response
    {
        if (!$user) {
            $user = $request->user();
        }

        $bounties = $this->userBountyService->getUserBountiesWithDeleted($user);
        return Inertia::render('Profile', [
            'user' => XPHelper::getUserWithXP($user),
            'bounties' => BountyResource::collection($bounties),
            'isOwner' => $request->user() && $request->user()->id === $user->id,
        ]);
    }
}

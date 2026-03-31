<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationInviteController extends Controller
{
    use AuthorizesRequests;
    public function __construct(private readonly OrganizationService $service) {}

    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('update', $organization);

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->service->invite($organization, $request->input('email'));

        return back()->with('success', "Invitation sent to {$request->input('email')}");
    }

    public function accept(Request $request, Organization $organization): RedirectResponse
    {
        $this->service->acceptInvite($request->query('token'), $request->user());

        return redirect()->route('organizations.show', $organization)
            ->with('success', "You have joined {$organization->name}.");
    }

    public function decline(Request $request, Organization $organization): RedirectResponse
    {
        $this->service->declineInvite($request->query('token'));

        return redirect()->route('home')
            ->with('success', 'Invitation declined.');
    }
}

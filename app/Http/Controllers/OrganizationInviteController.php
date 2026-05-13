<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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

        try {
            $this->service->invite($organization, $request->input('email'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('success', "Invitation sent to {$request->input('email')}");
    }

    public function accept(Request $request, Organization $organization): RedirectResponse
    {
        try {
            $this->service->acceptInvite($request->query('token'), $request->user());
        } catch (ValidationException $e) {
            return redirect()->route('dashboard')
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->withErrors(['token' => 'This invitation is no longer valid.']);
        }

        return redirect()->route('organizations.show', $organization)
            ->with('success', "You have joined {$organization->name}.");
    }

    public function decline(Request $request, Organization $organization): RedirectResponse
    {
        try {
            $this->service->declineInvite($request->query('token'));
        } catch (\Exception $e) {
        }

        return redirect()->route('dashboard')
            ->with('success', 'Invitation declined.');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class OrganizationController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:organizations,slug'],
        ]);

        $organization = Organization::create(array_merge($validated, [
            'owner_id' => $request->user()->id,
        ]));

        $organization->members()->attach($request->user()->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);
        return redirect()->route('organizations.show', $organization);
    }

    public function show(Request $request, Organization $organization): Response
    {
        $this->authorize('view', $organization);
        $members = $organization->members()
            ->withPivot('role', 'joined_at')
            ->get();
        return Inertia::render('Organizations/Show', [
            'organization' => $organization->load('owner'),
            'members' => $members,
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('update', $organization);// owner/admin modosithat
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:organizations,slug,' . $organization->id],
        ]);
        $organization->update($validated);
        return redirect()->route('organizations.show', $organization);
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);
        $organization->members()->detach();
        $organization->delete();
        return redirect()->route('dashboard');
    }

    public function members(Organization $organization): Response
    {
        $this->authorize('view', $organization);
        $members = $organization->members()
            ->withPivot('role', 'joined_at')
            ->get();
        return Inertia::render('Organizations/Members', [
            'organization' => $organization,
            'members' => $members,
        ]);
    }

    public function removeMember(Organization $organization, User $user): RedirectResponse
    {
        $this->authorize('update', $organization);
        if ($organization->owner_id === $user->id) {
            return back()->withErrors(['member' => 'Cannot remove the owner.']);
        }
        $organization->members()->detach($user->id);
        Mail::raw(
            "You have been removed from the organization \"{$organization->name}\" on GitCodeGud.",
            fn($msg) => $msg->to($user->email)->subject("Removed from {$organization->name}")
        );
        return back()->with('success', 'Member removed.');
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $organizations = $user->organizations()
            ->withPivot('role', 'joined_at')
            ->withCount('members')
            ->get();

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations,
        ]);
    }
}

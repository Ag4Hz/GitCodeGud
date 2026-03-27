<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Request;

class OrganizationController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:organizations,slug'],
        ]);

        $organization = Organization::create([
            ...$validated,
            'owner_id' => $request->user()->id,
        ]);

        $organization->members()->attach($request->user()->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);
        return redirect()->route('organizations.show', $organization);
    }

    public function show(Request $request, Organization $organization): Response
    {
        $this->authorize('view', $organization);
        return Inertia::render("Organization/Show", [
            'organization' => $organization->load('owner'),
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('update', $organization);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:organizations,slug,' . $organization->id],
        ]);
        $organization->update($validated);
        return redirect()->route('organizations.show', $organization);
    }
}

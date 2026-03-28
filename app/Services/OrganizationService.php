<?php

namespace App\Services;

use App\Mail\OrganizationInviteMail;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizationService
{
    public function invite(Organization $organization, string $email): void
    {
        $token = (string) Str::uuid();

        OrganizationInvite::create([
            'organization_id' => $organization->id,
            'email'           => $email,
            'token'           => $token,
            'expires_at'      => now()->addDays(7),
        ]);

        $acceptUrl  = route('organizations.invite.accept', [
            'organization' => $organization->id,
            'token'        => $token,
        ]);

        $declineUrl = route('organizations.invite.decline', [
            'organization' => $organization->id,
            'token'        => $token,
        ]);

        Mail::to($email)->send(new OrganizationInviteMail($organization, $acceptUrl, $declineUrl));
    }

    public function acceptInvite(string $token, User $user): void
    {
        $invite = OrganizationInvite::where('token', $token)->firstOrFail();

        if ($invite->isExpired()) {
            throw ValidationException::withMessages(['token' => 'This invitation has expired.']);
        }

        if ($invite->isAccepted()) {
            throw ValidationException::withMessages(['token' => 'This invitation has already been accepted.']);
        }

        $invite->organization->members()->attach($user->id, [
            'role'      => 'member',
            'joined_at' => now(),
        ]);

        $invite->update(['accepted_at' => now()]);
    }

    public function declineInvite(string $token): void
    {
        $invite = OrganizationInvite::where('token', $token)->firstOrFail();

        $invite->delete();
    }
}

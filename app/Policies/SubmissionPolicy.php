<?php

namespace App\Policies;

use App\Models\Bounty;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubmissionPolicy
{
    public function create(User $user, Bounty $bounty): Response
    {
        if ($bounty->issue->repo->user_id === $user->id) {
            return Response::deny('You cannot submit to your own bounty.');
        }
        if ($bounty->status !== 'open') {
            return Response::deny('This bounty is not accepting submissions.');
        }

        $existingSubmission = $bounty->submissions()
            ->where('user_id', $user->id)
            ->first();
        if ($existingSubmission && $existingSubmission->status !== 'rejected') {
            return Response::deny('You have already submitted a solution for this bounty.');
        }

        return Response::allow();
    }
    public function view(User $user, Submission $submission): Response
    {
        if ($submission->user_id === $user->id) {
            return Response::allow();
        }

        if ($submission->bounty->issue->repo->user_id === $user->id) {
            return Response::allow();
        }

        return Response::deny('You cannot view this submission.');
    }

    public function updateStatus(User $user, Submission $submission): Response
    {
        if ($submission->bounty->issue->repo->user_id !== $user->id) {
            return Response::deny('Only the bounty owner can update submission status.');
        }

        return Response::allow();
    }
    public function viewSubmissions(User $user, Bounty $bounty): Response
    {
        if ($bounty->issue->repo->user_id !== $user->id) {
            return Response::deny('Only the bounty owner can view all submissions.');
        }
        return Response::allow();
    }
}

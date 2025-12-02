<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmissionStoreRequest;
use App\Models\Bounty;
use App\Models\Submission;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionController extends Controller
{
    use AuthorizesRequests;

    public function create(Request $request, Bounty $bounty): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'You must be logged in to submit.');
        }

        if ($bounty->issue->repo->user_id === $user->id) {
            abort(403, 'You cannot submit to your own bounty.');
        }

        if ($bounty->status !== 'open') {
            abort(403, 'This bounty is not accepting submissions.');
        }

        $existingSubmission = $bounty->submissions()
            ->where('user_id', $user->id)
            ->first();

        if ($existingSubmission && $existingSubmission->status !== 'rejected') {
            abort(403, 'You have already submitted a solution for this bounty.');
        }

        return Inertia::render('submissions/Create', [
            'bounty' => $bounty->load(['issue.repo']),
        ]);
    }
    public function store(SubmissionStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $bounty = Bounty::with('issue.repo')->findOrFail($validated['bounty_id']);
        $user = $request->user();

        $this->authorize('create', [Submission::class, $bounty]);

        $existingSubmission = $bounty->submissions()
            ->where('user_id', $user->id)
            ->first();

        if ($existingSubmission && $existingSubmission->status === 'rejected') {
            $existingSubmission->update([
                'pr_url' => $validated['pr_url'],
                'status' => 'pending',
                'provider' => $request->provider(),
                'updated_at' => now(),
            ]);

            return redirect()
                ->route('bounties.show', $bounty)
                ->with('success', 'Solution resubmitted successfully! Your submission is now pending review.');
        }

        Submission::create([
            'bounty_id' => $validated['bounty_id'],
            'user_id' => $user->id,
            'pr_url' => $validated['pr_url'],
            'status' => 'pending',
            'provider' => $request->provider(),
        ]);

        return redirect()
            ->route('bounties.show', $bounty)
            ->with('success', 'Solution submitted successfully! Your submission is now pending review.');
    }
}

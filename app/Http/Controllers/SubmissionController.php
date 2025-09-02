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
            ->exists();

        if ($existingSubmission) {
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

        if ($bounty->issue->repo->user_id === $user->id) {
            return back()->withErrors(['error' => 'You cannot submit to your own bounty.']);
        }

        if ($bounty->status !== 'open') {
            return back()->withErrors(['error' => 'This bounty is not accepting submissions.']);
        }

        $existingSubmission = $bounty->submissions()
            ->where('user_id', $user->id)
            ->exists();

        if ($existingSubmission) {
            return back()->withErrors(['error' => 'You have already submitted a solution for this bounty.']);
        }

        $submission = Submission::create([
            'bounty_id' => $validated['bounty_id'],
            'user_id' => auth()->id(),
            'pr_url' => $validated['pr_url'],
            'status' => 'pending',
        ]);

        return redirect()
            ->route('bounties.show', $bounty)
            ->with('success', 'Solution submitted successfully! Your submission is now pending review.');
    }

    /**
     * Show submissions for a specific bounty (for bounty owners)
     */
    public function indexForBounty(Request $request, Bounty $bounty): Response
    {
        $user = $request->user();

        if (!$user || $bounty->issue->repo->user_id !== $user->id) {
            abort(403, 'Only the bounty owner can view all submissions.');
        }

        return Inertia::render('bounties/Submissions', [
            'bounty' => $bounty->load(['issue.repo']),
            'submissions' => $bounty->submissions()->with(['user'])->latest()->paginate(10),
        ]);
    }

    /**
     * Update submission status (accept/reject)
     */
    public function updateStatus(Request $request, Submission $submission): RedirectResponse
    {

        $user = $request->user();
        if (!$user || $submission->bounty->issue->repo->user_id !== $user->id) {
            abort(403, 'Only the bounty owner can update submission status.');
        }
        $request->validate([
            'status' => ['required', 'in:accepted,rejected']
        ]);

        $submission->update([
            'status' => $request->status
        ]);

        $message = $request->status === 'accepted'
            ? 'Submission accepted successfully!'
            : 'Submission rejected.';

        return redirect()
            ->back()
            ->with('success', $message);
    }
}

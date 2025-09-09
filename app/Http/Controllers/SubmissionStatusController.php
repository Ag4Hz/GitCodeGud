<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Helpers\XPHelper;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubmissionStatusController extends Controller
{
    use AuthorizesRequests;

    public function update(Request $request, Submission $submission): RedirectResponse
    {
        $user = $request->user();

        if (!$user || $submission->bounty->issue->repo->user_id !== $user->id) {
            abort(403, 'Only the bounty owner can update submission status.');
        }

        $request->validate([
            'status' => ['required', 'in:accepted,rejected']
        ]);

        $oldStatus = $submission->status;

        $submission->update([
            'status' => $request->status
        ]);

        if ($request->status === 'accepted' && $oldStatus !== 'accepted') {
            XPHelper::awardSubmissionXP($submission);
            return redirect()->back()->with('success', 'Submission accepted successfully! XP awarded to contributor.');
        }

        $message = $request->status === 'accepted'
            ? 'Submission accepted successfully!'
            : 'Submission rejected.';

        return redirect()->back()->with('success', $message);
    }
}

<?php

namespace App\Observers;

use App\Events\LeaderboardUpdated;
use App\Events\SubmissionStatusChanged;
use App\Models\Submission;
use App\Helpers\XPHelper;
use Illuminate\Support\Facades\Cache;

class SubmissionObserver
{
    public function updated(Submission $submission): void
    {
        if ($submission->wasChanged('status') &&
            $submission->status === 'accepted' &&
            $submission->getOriginal('status') !== 'accepted') {

            XPHelper::awardSubmissionXP($submission);
            $submission->user->refresh();
            LeaderboardUpdated::dispatch($submission->user, $submission->user->xp);
            Cache::forget("recommendations_user_{$submission->user_id}");
        }

        if ($submission->wasChanged('status')) {
            SubmissionStatusChanged::dispatch($submission);
        }
    }
}

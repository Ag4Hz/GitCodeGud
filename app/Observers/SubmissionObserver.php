<?php

namespace App\Observers;

use App\Events\LeaderboardUpdated;
use App\Models\Submission;
use App\Helpers\XPHelper;

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
        }
    }
}

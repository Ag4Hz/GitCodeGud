<?php

namespace App\Observers;

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
        }
    }
}

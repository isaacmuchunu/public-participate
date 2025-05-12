<?php

namespace App\Observers;

use App\Jobs\SendSubmissionAggregatedNotification;
use App\Models\Submission;

class SubmissionObserver
{
    public function updated(Submission $submission): void
    {
        if (! $submission->wasChanged('status')) {
            return;
        }

        if (! in_array($submission->status, ['included', 'aggregated'], true)) {
            return;
        }

        $submission->loadMissing('user', 'bill');

        SendSubmissionAggregatedNotification::dispatch($submission);
    }
}

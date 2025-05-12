<?php

namespace App\Jobs;

use App\Models\Submission;
use App\Notifications\Submission\SubmissionAggregatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSubmissionAggregatedNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly Submission $submission)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $user = $this->submission->user;

        if (! $user) {
            return;
        }

        $user->notify(new SubmissionAggregatedNotification($this->submission));
    }
}

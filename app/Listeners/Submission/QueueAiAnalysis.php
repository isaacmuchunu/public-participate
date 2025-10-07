<?php

namespace App\Listeners\Submission;

use App\Events\Submission\SubmissionCreated;
use App\Jobs\AI\AnalyzeSubmissionJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class QueueAiAnalysis implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(SubmissionCreated $event): void
    {
        try {
            // Queue AI analysis job
            AnalyzeSubmissionJob::dispatch($event->submission)
                ->onQueue('analysis')
                ->delay(now()->addSeconds(5)); // Small delay to allow transaction to complete

            Log::info("Queued AI analysis for submission {$event->submission->id}");
        } catch (\Exception $e) {
            Log::error("Failed to queue AI analysis for submission {$event->submission->id}: {$e->getMessage()}");
        }
    }
}

<?php

namespace App\Listeners\Engagement;

use App\Events\Engagement\MessageSent;
use App\Notifications\Engagement\NewEngagementMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyLegislator implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(MessageSent $event): void
    {
        try {
            $legislator = $event->engagement->recipient;

            // Send notification to legislator
            $legislator->notify(new NewEngagementMessage($event->engagement));

            Log::info("Notified legislator {$legislator->id} about engagement {$event->engagement->id}");
        } catch (\Exception $e) {
            Log::error("Failed to notify legislator about engagement {$event->engagement->id}: {$e->getMessage()}");
        }
    }
}

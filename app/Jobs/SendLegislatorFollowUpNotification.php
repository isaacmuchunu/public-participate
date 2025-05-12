<?php

namespace App\Jobs;

use App\Models\CitizenEngagement;
use App\Notifications\Engagement\LegislatorFollowUpNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendLegislatorFollowUpNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly CitizenEngagement $engagement)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $recipient = $this->engagement->recipient;

        if (! $recipient) {
            return;
        }

        $this->engagement->loadMissing('bill', 'sender');

        $recipient->notify(new LegislatorFollowUpNotification($this->engagement));
    }
}

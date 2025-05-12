<?php

namespace App\Jobs;

use App\Models\Bill;
use App\Models\User;
use App\Notifications\Bill\BillParticipationOpenedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendBillParticipationOpenedNotifications implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly Bill $bill)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        User::query()
            ->where('role', 'citizen')
            ->where('is_verified', true)
            ->chunkById(200, function ($users) {
                Notification::send($users, new BillParticipationOpenedNotification($this->bill));
            });
    }
}

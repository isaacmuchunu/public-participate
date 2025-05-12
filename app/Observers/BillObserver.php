<?php

namespace App\Observers;

use App\Jobs\SendBillParticipationOpenedNotifications;
use App\Jobs\SendBillPublishedNotifications;
use App\Models\Bill;
use Illuminate\Support\Carbon;

class BillObserver
{
    public function created(Bill $bill): void
    {
        SendBillPublishedNotifications::dispatch($bill->fresh());
    }

    public function updated(Bill $bill): void
    {
        if ($this->shouldNotifyParticipationOpened($bill)) {
            SendBillParticipationOpenedNotifications::dispatch($bill->fresh());
        }
    }

    private function shouldNotifyParticipationOpened(Bill $bill): bool
    {
        if ($bill->wasChanged('status') && $bill->status === 'open_for_participation') {
            return true;
        }

        if (! $bill->participation_start_date) {
            return false;
        }

        $original = $bill->getOriginal('participation_start_date');

        $previousDate = $original ? Carbon::parse($original) : null;

        return $bill->wasChanged('participation_start_date')
            && $bill->participation_start_date->isPast()
            && ($previousDate === null || $previousDate->isFuture());
    }
}

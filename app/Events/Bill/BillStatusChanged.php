<?php

namespace App\Events\Bill;

use App\Models\Bill;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Bill $bill,
        public string $oldStatus,
        public string $newStatus
    ) {
        //
    }
}

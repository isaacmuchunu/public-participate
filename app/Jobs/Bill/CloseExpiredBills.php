<?php

namespace App\Jobs\Bill;

use App\Services\Bill\BillLifecycleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CloseExpiredBills implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(BillLifecycleService $lifecycleService): void
    {
        try {
            $closedCount = $lifecycleService->closeExpiredBills();

            Log::info("CloseExpiredBills job completed. Closed {$closedCount} bills.");
        } catch (\Exception $e) {
            Log::error("CloseExpiredBills job failed: {$e->getMessage()}");

            throw $e;
        }
    }
}

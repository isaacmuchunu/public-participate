<?php

namespace App\Jobs\Bill;

use App\Services\Bill\BillLifecycleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OpenScheduledBills implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(BillLifecycleService $lifecycleService): void
    {
        try {
            $openedCount = $lifecycleService->openScheduledBills();

            Log::info("OpenScheduledBills job completed. Opened {$openedCount} bills.");
        } catch (\Exception $e) {
            Log::error("OpenScheduledBills job failed: {$e->getMessage()}");

            throw $e;
        }
    }
}

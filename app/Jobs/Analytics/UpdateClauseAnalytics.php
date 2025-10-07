<?php

namespace App\Jobs\Analytics;

use App\Models\Bill;
use App\Models\BillClause;
use App\Models\ClauseAnalytics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateClauseAnalytics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Bill $bill
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $clauses = $this->bill->clauses;

            foreach ($clauses as $clause) {
                $this->updateClauseAnalytics($clause);
            }

            Log::info("Updated clause analytics for bill {$this->bill->id}");
        } catch (\Exception $e) {
            Log::error("Failed to update clause analytics for bill {$this->bill->id}: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Update analytics for a specific clause
     */
    protected function updateClauseAnalytics(BillClause $clause): void
    {
        // Calculate submission statistics for this clause
        $submissions = DB::table('submissions')
            ->where('bill_id', $clause->bill_id)
            ->whereJsonContains('content', $clause->clause_number)
            ->get();

        $supportCount = $submissions->where('submission_type', 'support')->count();
        $opposeCount = $submissions->where('submission_type', 'oppose')->count();
        $amendCount = $submissions->where('submission_type', 'amend')->count();
        $neutralCount = $submissions->where('submission_type', 'neutral')->count();

        $totalSubmissions = $submissions->count();
        $sentimentScore = $totalSubmissions > 0
            ? (($supportCount - $opposeCount) / $totalSubmissions) * 100
            : 0;

        // Update or create clause analytics
        ClauseAnalytics::updateOrCreate(
            ['clause_id' => $clause->id],
            [
                'total_submissions' => $totalSubmissions,
                'support_count' => $supportCount,
                'oppose_count' => $opposeCount,
                'amend_count' => $amendCount,
                'neutral_count' => $neutralCount,
                'sentiment_score' => $sentimentScore,
                'views_count' => $clause->views_count ?? 0,
                'last_calculated_at' => now(),
            ]
        );
    }
}

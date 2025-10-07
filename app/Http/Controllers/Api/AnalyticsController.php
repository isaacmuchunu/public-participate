<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillClause;
use App\Services\SubmissionWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function __construct(
        protected SubmissionWorkflowService $workflowService
    ) {
        //
    }

    /**
     * Get analytics for a bill
     */
    public function billAnalytics(Bill $bill): JsonResponse
    {
        $this->authorize('view', $bill);

        $stats = $this->workflowService->getSubmissionStats($bill);

        // Add additional metrics
        $additionalStats = [
            'views_count' => $bill->views_count,
            'participation_rate' => $this->calculateParticipationRate($bill),
            'avg_submission_length' => $this->getAverageSubmissionLength($bill),
            'top_counties' => $this->getTopCounties($bill),
            'timeline_data' => $this->getTimelineData($bill),
        ];

        return response()->json([
            'bill' => [
                'id' => $bill->id,
                'title' => $bill->title,
                'status' => $bill->status,
            ],
            'statistics' => array_merge($stats, $additionalStats),
        ]);
    }

    /**
     * Get analytics for a specific clause
     */
    public function clauseAnalytics(Bill $bill, BillClause $clause): JsonResponse
    {
        $this->authorize('view', $bill);

        if ($clause->bill_id !== $bill->id) {
            return response()->json([
                'message' => 'Clause does not belong to this bill',
            ], 404);
        }

        $analytics = $clause->analytics;

        if (! $analytics) {
            return response()->json([
                'message' => 'No analytics available for this clause yet',
                'clause' => [
                    'id' => $clause->id,
                    'clause_number' => $clause->clause_number,
                    'title' => $clause->title,
                ],
                'statistics' => [
                    'total_submissions' => 0,
                    'support_count' => 0,
                    'oppose_count' => 0,
                    'amend_count' => 0,
                    'neutral_count' => 0,
                    'sentiment_score' => 0,
                ],
            ]);
        }

        return response()->json([
            'clause' => [
                'id' => $clause->id,
                'clause_number' => $clause->clause_number,
                'title' => $clause->title,
            ],
            'statistics' => [
                'total_submissions' => $analytics->total_submissions,
                'support_count' => $analytics->support_count,
                'oppose_count' => $analytics->oppose_count,
                'amend_count' => $analytics->amend_count,
                'neutral_count' => $analytics->neutral_count,
                'sentiment_score' => $analytics->sentiment_score,
                'views_count' => $analytics->views_count,
                'last_calculated_at' => $analytics->last_calculated_at,
            ],
        ]);
    }

    /**
     * Calculate participation rate
     */
    protected function calculateParticipationRate(Bill $bill): float
    {
        // Rough estimate: submissions per 1000 eligible citizens
        $totalCitizens = 1000; // This should come from actual data

        return round(($bill->submissions_count / $totalCitizens) * 100, 2);
    }

    /**
     * Get average submission length
     */
    protected function getAverageSubmissionLength(Bill $bill): int
    {
        $avg = $bill->submissions()
            ->selectRaw('AVG(LENGTH(content)) as avg_length')
            ->value('avg_length');

        return (int) $avg;
    }

    /**
     * Get top counties by submission count
     */
    protected function getTopCounties(Bill $bill): array
    {
        return DB::table('submissions')
            ->join('users', 'submissions.user_id', '=', 'users.id')
            ->join('counties', 'users.county_id', '=', 'counties.id')
            ->where('submissions.bill_id', $bill->id)
            ->select('counties.name', DB::raw('COUNT(*) as count'))
            ->groupBy('counties.id', 'counties.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Get submission timeline data
     */
    protected function getTimelineData(Bill $bill): array
    {
        return DB::table('submissions')
            ->where('bill_id', $bill->id)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}

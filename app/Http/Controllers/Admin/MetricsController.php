<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillView;
use App\Models\CommentSubmission;
use App\Models\ParticipationView;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MetricsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', '30d');
        $days = $this->getDaysFromPeriod($period);

        $metrics = [
            'total_bills' => Bill::count(),
            'total_submissions' => Submission::count(),
            'total_users' => User::count(),
            'total_views' => BillView::count(),
            'participation_rate' => $this->calculateParticipationRate(),
            'average_comments_per_bill' => $this->calculateAverageCommentsPerBill(),
            'task_completion_rate' => $this->calculateTaskCompletionRate($days),
            'average_time_to_comment' => $this->calculateAverageTimeToComment($days),
            'mobile_vs_desktop' => $this->calculateMobileVsDesktop($days),
            'recent_activity' => $this->getRecentActivity($days),
        ];

        return Inertia::render('Admin/Metrics', [
            'metrics' => $metrics,
            'period' => $period,
        ]);
    }

    private function getDaysFromPeriod(string $period): int
    {
        return match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 30,
        };
    }

    private function calculateParticipationRate(): float
    {
        $totalViews = BillView::count();
        $totalSubmissions = Submission::count();

        return $totalViews > 0 ? $totalSubmissions / $totalViews : 0;
    }

    private function calculateAverageCommentsPerBill(): float
    {
        $totalBills = Bill::count();
        $totalSubmissions = Submission::count();

        return $totalBills > 0 ? $totalSubmissions / $totalBills : 0;
    }

    private function calculateTaskCompletionRate(int $days): float
    {
        $startDate = now()->subDays($days);

        $totalViews = ParticipationView::where('viewed_at', '>=', $startDate)->count();
        $totalSubmissions = CommentSubmission::where('submitted_at', '>=', $startDate)->count();

        return $totalViews > 0 ? $totalSubmissions / $totalViews : 0;
    }

    private function calculateAverageTimeToComment(int $days): int
    {
        $startDate = now()->subDays($days);

        $submissions = CommentSubmission::where('submitted_at', '>=', $startDate)
            ->with('bill')
            ->get();

        if ($submissions->isEmpty()) {
            return 0;
        }

        $totalTime = 0;
        $count = 0;

        foreach ($submissions as $submission) {
            $bill = $submission->bill;
            if ($bill && $bill->created_at) {
                $viewTime = ParticipationView::where('ip_address', $submission->ip_address)
                    ->where('viewed_at', '>=', $bill->created_at)
                    ->where('viewed_at', '<=', $submission->submitted_at)
                    ->first();

                if ($viewTime) {
                    $timeDiff = $submission->submitted_at->diffInSeconds($viewTime->viewed_at);
                    $totalTime += $timeDiff;
                    $count++;
                }
            }
        }

        return $count > 0 ? (int) ($totalTime / $count) : 0;
    }

    private function calculateMobileVsDesktop(int $days): array
    {
        $startDate = now()->subDays($days);

        $views = BillView::where('viewed_at', '>=', $startDate)->get();

        $mobile = 0;
        $desktop = 0;

        foreach ($views as $view) {
            $userAgent = $view->user_agent ?? '';
            if ($this->isMobile($userAgent)) {
                $mobile++;
            } else {
                $desktop++;
            }
        }

        $total = $mobile + $desktop;
        return [
            'mobile' => $total > 0 ? $mobile / $total : 0,
            'desktop' => $total > 0 ? $desktop / $total : 0,
        ];
    }

    private function getRecentActivity(int $days): array
    {
        $startDate = now()->subDays($days);

        $activity = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            $submissions = CommentSubmission::whereDate('submitted_at', $date)->count();
            $views = BillView::whereDate('viewed_at', $date)->count();

            $activity[] = [
                'date' => $date,
                'submissions' => $submissions,
                'views' => $views,
            ];
        }

        return $activity;
    }

    private function isMobile(string $userAgent): bool
    {
        $mobileKeywords = ['Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry', 'Windows Phone'];

        foreach ($mobileKeywords as $keyword) {
            if (str_contains($userAgent, $keyword)) {
                return true;
            }
        }

        return false;
    }
}

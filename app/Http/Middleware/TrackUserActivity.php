<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Track page views
        if ($request->method() === 'GET') {
            $this->trackPageView($request);
        }

        // Track form submissions
        if ($request->method() === 'POST' && $request->has('content')) {
            $this->trackCommentSubmission($request);
        }

        return $next($request);
    }

    private function trackPageView(Request $request): void
    {
        $user = Auth::user();
        $routeName = $request->route()?->getName();

        // Track bill views
        if (str_contains($routeName, 'bills.show')) {
            $billId = $request->route('bill')->id ?? null;
            if ($billId) {
                \App\Models\BillView::create([
                    'bill_id' => $billId,
                    'user_id' => $user?->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'viewed_at' => now(),
                ]);
            }
        }

        // Track participation page views
        if (str_contains($routeName, 'bills.participate')) {
            \App\Models\ParticipationView::create([
                'user_id' => $user?->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'viewed_at' => now(),
            ]);
        }
    }

    private function trackCommentSubmission(Request $request): void
    {
        $user = Auth::user();

        // Track comment submission time
        if ($request->has('bill_id') && $request->has('clause_id')) {
            \App\Models\CommentSubmission::create([
                'user_id' => $user?->id,
                'bill_id' => $request->bill_id,
                'clause_id' => $request->clause_id,
                'submission_type' => $request->submission_type ?? 'comment',
                'content_length' => strlen($request->content),
                'submitted_at' => now(),
                'ip_address' => $request->ip(),
            ]);
        }
    }
}

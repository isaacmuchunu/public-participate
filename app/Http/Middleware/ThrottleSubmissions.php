<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleSubmissions
{
    private const MAX_DAILY_SUBMISSIONS = 10;

    private const LOCKOUT_MINUTES = 1440; // 24 hours

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If no authenticated user, use IP-based throttling
        if (! $user) {
            return $this->handleIpBasedThrottle($request, $next);
        }

        // User-based throttling
        return $this->handleUserBasedThrottle($request, $next, $user);
    }

    /**
     * Handle IP-based rate limiting for unauthenticated users
     */
    private function handleIpBasedThrottle(Request $request, Closure $next): Response
    {
        $key = 'submission_ip_'.$request->ip();

        $executed = RateLimiter::attempt(
            $key,
            self::MAX_DAILY_SUBMISSIONS,
            function () {},
            self::LOCKOUT_MINUTES * 60 // Convert to seconds
        );

        if (! $executed) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too many submission attempts. Please try again later.',
                'retry_after' => $seconds,
                'retry_after_human' => $this->formatRetryAfter($seconds),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return $next($request);
    }

    /**
     * Handle user-based rate limiting for authenticated users
     */
    private function handleUserBasedThrottle(Request $request, Closure $next, $user): Response
    {
        $key = 'submission_user_'.$user->id;

        // Get current submission count for today
        $count = Cache::get($key, 0);

        // Check if limit exceeded
        if ($count >= self::MAX_DAILY_SUBMISSIONS) {
            $expiresAt = Cache::get($key.'_expires_at');
            $seconds = $expiresAt ? $expiresAt - now()->timestamp : 0;

            return response()->json([
                'message' => 'Daily submission limit reached. You can submit '
                    .self::MAX_DAILY_SUBMISSIONS.' submissions per day.',
                'current_count' => $count,
                'limit' => self::MAX_DAILY_SUBMISSIONS,
                'retry_after' => $seconds,
                'retry_after_human' => $this->formatRetryAfter($seconds),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        // Increment counter
        if ($count === 0) {
            // First submission of the day, set expiry
            $expiresAt = now()->endOfDay()->timestamp;
            Cache::put($key, 1, now()->endOfDay());
            Cache::put($key.'_expires_at', $expiresAt, now()->endOfDay());
        } else {
            Cache::increment($key);
        }

        // Add submission count to response headers
        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', self::MAX_DAILY_SUBMISSIONS);
        $response->headers->set('X-RateLimit-Remaining', max(0, self::MAX_DAILY_SUBMISSIONS - $count - 1));

        return $response;
    }

    /**
     * Format retry_after seconds into human-readable format
     */
    private function formatRetryAfter(int $seconds): string
    {
        if ($seconds <= 0) {
            return 'now';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return $hours.' hour'.($hours !== 1 ? 's' : '')
                .($minutes > 0 ? ' and '.$minutes.' minute'.($minutes !== 1 ? 's' : '') : '');
        }

        if ($minutes > 0) {
            return $minutes.' minute'.($minutes !== 1 ? 's' : '');
        }

        return $seconds.' second'.($seconds !== 1 ? 's' : '');
    }

    /**
     * Get current submission count for user
     */
    public static function getCurrentCount($userId): int
    {
        return Cache::get('submission_user_'.$userId, 0);
    }

    /**
     * Check if user can submit
     */
    public static function canSubmit($userId): bool
    {
        $count = self::getCurrentCount($userId);

        return $count < self::MAX_DAILY_SUBMISSIONS;
    }

    /**
     * Get remaining submissions for user
     */
    public static function getRemainingSubmissions($userId): int
    {
        $count = self::getCurrentCount($userId);

        return max(0, self::MAX_DAILY_SUBMISSIONS - $count);
    }
}

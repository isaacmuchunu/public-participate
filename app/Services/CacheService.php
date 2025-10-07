<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\County;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Cache TTL constants (in seconds)
     */
    private const TTL_SHORT = 300;      // 5 minutes - frequently changing data
    private const TTL_MEDIUM = 3600;    // 1 hour - moderately stable data
    private const TTL_LONG = 86400;     // 24 hours - stable reference data

    /**
     * Cache tags for organized cache management
     */
    private const TAG_BILLS = 'bills';
    private const TAG_GEOGRAPHIC = 'geographic';
    private const TAG_ANALYTICS = 'analytics';
    private const TAG_SUBMISSIONS = 'submissions';

    /**
     * Cache open bills with short TTL (5 minutes)
     * Used for: Homepage, bill listing pages
     */
    public function cacheOpenBills(): mixed
    {
        return Cache::tags([self::TAG_BILLS])
            ->remember('bills:open', self::TTL_SHORT, function () {
                return Bill::with('summary')
                    ->where('status', 'open')
                    ->where('participation_end_date', '>=', now())
                    ->latest('created_at')
                    ->get();
            });
    }

    /**
     * Cache bill with complete clause hierarchy (1 hour TTL)
     * Used for: Bill detail pages, submission forms
     */
    public function cacheBillWithClauses(Bill $bill): Bill
    {
        return Cache::tags([self::TAG_BILLS])
            ->remember("bill:{$bill->id}:full", self::TTL_MEDIUM, function () use ($bill) {
                return $bill->load([
                    'summary',
                    'topLevelClauses' => function ($query) {
                        $query->with([
                            'children' => function ($q) {
                                $q->with([
                                    'children.analytics',
                                    'analytics',
                                ])->orderBy('display_order');
                            },
                            'analytics',
                        ])->orderBy('display_order');
                    },
                    'createdBy:id,name,email',
                ]);
            });
    }

    /**
     * Cache bill summary only (medium TTL)
     * Used for: Quick bill previews, lists
     */
    public function cacheBillSummary(Bill $bill): Bill
    {
        return Cache::tags([self::TAG_BILLS])
            ->remember("bill:{$bill->id}:summary", self::TTL_MEDIUM, function () use ($bill) {
                return $bill->load('summary');
            });
    }

    /**
     * Cache geographic data (24 hour TTL)
     * Used for: User registration, filtering, geographic reports
     */
    public function cacheGeographicData(): mixed
    {
        return Cache::tags([self::TAG_GEOGRAPHIC])
            ->remember('geo:complete', self::TTL_LONG, function () {
                return County::with([
                    'constituencies' => function ($query) {
                        $query->with('wards')->orderBy('name');
                    },
                ])->orderBy('name')->get();
            });
    }

    /**
     * Cache counties only (24 hour TTL)
     */
    public function cacheCounties(): mixed
    {
        return Cache::tags([self::TAG_GEOGRAPHIC])
            ->remember('geo:counties', self::TTL_LONG, function () {
                return County::orderBy('name')->get();
            });
    }

    /**
     * Cache clause analytics for a bill (short TTL)
     * Used for: Analytics dashboards, reporting
     */
    public function cacheClauseAnalytics(int $billId): mixed
    {
        return Cache::tags([self::TAG_ANALYTICS, self::TAG_BILLS])
            ->remember("bill:{$billId}:analytics", self::TTL_SHORT, function () use ($billId) {
                return Bill::with([
                    'topLevelClauses.analytics',
                    'topLevelClauses.children.analytics',
                ])->findOrFail($billId);
            });
    }

    /**
     * Cache user submissions for a bill (short TTL)
     * Used for: User profile, submission history
     */
    public function cacheUserBillSubmissions(int $userId, int $billId): mixed
    {
        return Cache::tags([self::TAG_SUBMISSIONS])
            ->remember("user:{$userId}:bill:{$billId}:submissions", self::TTL_SHORT, function () use ($userId, $billId) {
                return \App\Models\Submission::where('user_id', $userId)
                    ->where('bill_id', $billId)
                    ->with(['clause', 'bill'])
                    ->latest()
                    ->get();
            });
    }

    /**
     * Clear all bill-related cache
     */
    public function clearBillCache(?Bill $bill = null): void
    {
        if ($bill) {
            // Clear specific bill caches
            Cache::forget("bill:{$bill->id}:full");
            Cache::forget("bill:{$bill->id}:summary");
            Cache::forget("bill:{$bill->id}:analytics");
        }

        // Clear all bills cache
        Cache::tags([self::TAG_BILLS])->flush();
    }

    /**
     * Clear submission cache for a user and bill
     */
    public function clearSubmissionCache(int $userId, ?int $billId = null): void
    {
        if ($billId) {
            Cache::forget("user:{$userId}:bill:{$billId}:submissions");
        }

        Cache::tags([self::TAG_SUBMISSIONS])->flush();
    }

    /**
     * Clear geographic data cache
     */
    public function clearGeographicCache(): void
    {
        Cache::tags([self::TAG_GEOGRAPHIC])->flush();
    }

    /**
     * Clear analytics cache
     */
    public function clearAnalyticsCache(?int $billId = null): void
    {
        if ($billId) {
            Cache::forget("bill:{$billId}:analytics");
        }

        Cache::tags([self::TAG_ANALYTICS])->flush();
    }

    /**
     * Warm up critical caches
     * Run this after deployments or cache clears
     */
    public function warmCache(): void
    {
        // Cache open bills
        $this->cacheOpenBills();

        // Cache geographic data
        $this->cacheGeographicData();
        $this->cacheCounties();

        // Cache active bills with clauses
        $activeBills = Bill::where('status', 'open')
            ->where('participation_end_date', '>=', now())
            ->limit(10)
            ->get();

        foreach ($activeBills as $bill) {
            $this->cacheBillWithClauses($bill);
            $this->cacheBillSummary($bill);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $keys = [
            'bills:open',
            'geo:complete',
            'geo:counties',
        ];

        $stats = [
            'hits' => 0,
            'misses' => 0,
            'cached_items' => 0,
        ];

        foreach ($keys as $key) {
            if (Cache::has($key)) {
                $stats['cached_items']++;
            }
        }

        return $stats;
    }
}

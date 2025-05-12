<?php

namespace App\Http\Controllers\Legislator;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\LegislatorBillResource;
use App\Http\Resources\LegislatorHighlightResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Bill;
use App\Models\LegislatorHighlight;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BillController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $house = $this->resolveHouse($user);
        $filters = $request->only(['status', 'search']);

        $baseQuery = Bill::query()
            ->withCount([
                'submissions',
                'submissions as pending_submissions_count' => fn ($query) => $query->where('status', 'pending'),
                'submissions as aggregated_submissions_count' => fn ($query) => $query->where('status', 'aggregated'),
                'highlights as highlights_count' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->where(function (Builder $query) use ($house) {
                $query->where('house', $house)
                    ->orWhere('house', 'both');
            });

        $bills = (clone $baseQuery)
            ->when($filters['status'] ?? null, function (Builder $query, string $status) {
                return match ($status) {
                    'open' => $query->where('status', 'open_for_participation'),
                    'closed' => $query->where('status', 'closed'),
                    'draft' => $query->where('status', 'draft'),
                    default => $query,
                };
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('bill_number', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('participation_end_date')
            ->paginate(12)
            ->withQueryString();

        $metrics = [
            'open' => (clone $baseQuery)->where('status', 'open_for_participation')->count(),
            'closingSoon' => (clone $baseQuery)
                ->where('status', 'open_for_participation')
                ->whereBetween('participation_end_date', [Carbon::now(), Carbon::now()->addDays(7)])
                ->count(),
            'recentlyClosed' => (clone $baseQuery)
                ->where('status', 'closed')
                ->whereBetween('updated_at', [Carbon::now()->subDays(14), Carbon::now()])
                ->count(),
            'highlights' => LegislatorHighlight::query()->where('user_id', $user->id)->count(),
        ];

        return Inertia::render('Legislator/Bills/Index', [
            'bills' => LegislatorBillResource::collection($bills),
            'filters' => $filters,
            'metrics' => $metrics,
        ]);
    }

    public function show(Request $request, Bill $bill): Response
    {
        $user = $request->user();
        $house = $this->resolveHouse($user);

        abort_unless(in_array($bill->house, [$house, 'both'], true), 403);

        $bill->loadMissing(['summary']);

        $filters = $request->only(['status', 'type', 'county']);

        $submissionsQuery = Submission::query()
            ->with(['user:id,name,email', 'bill:id,title'])
            ->where('bill_id', $bill->id)
            ->orderByDesc('created_at');

        $submissions = (clone $submissionsQuery)
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('submission_type', $type))
            ->when($filters['county'] ?? null, fn (Builder $query, string $county) => $query->where('submitter_county', $county))
            ->paginate(15)
            ->withQueryString();

        $aggregation = [
            'byType' => (clone $submissionsQuery)
                ->select('submission_type', DB::raw('count(*) as total'))
                ->groupBy('submission_type')
                ->pluck('total', 'submission_type')
                ->all(),
            'byStatus' => (clone $submissionsQuery)
                ->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
            'byCounty' => (clone $submissionsQuery)
                ->whereNotNull('submitter_county')
                ->select('submitter_county', DB::raw('count(*) as total'))
                ->groupBy('submitter_county')
                ->orderByDesc('total')
                ->limit(10)
                ->pluck('total', 'submitter_county')
                ->all(),
        ];

        $highlights = LegislatorHighlight::query()
            ->with(['submission:id,tracking_id,submission_type,content,submitter_name', 'bill:id,title'])
            ->where('bill_id', $bill->id)
            ->where('user_id', $user->id)
            ->latest('highlighted_at')
            ->get();

        $availableFilters = [
            'status' => Submission::query()
                ->where('bill_id', $bill->id)
                ->distinct()
                ->pluck('status')
                ->values(),
            'type' => Submission::query()
                ->where('bill_id', $bill->id)
                ->distinct()
                ->pluck('submission_type')
                ->values(),
            'counties' => Submission::query()
                ->where('bill_id', $bill->id)
                ->whereNotNull('submitter_county')
                ->distinct()
                ->orderBy('submitter_county')
                ->pluck('submitter_county')
                ->values(),
        ];

        return Inertia::render('Legislator/Bills/Show', [
            'bill' => LegislatorBillResource::make($bill),
            'submissions' => SubmissionResource::collection($submissions),
            'aggregation' => $aggregation,
            'highlights' => LegislatorHighlightResource::collection($highlights),
            'filters' => $filters,
            'availableFilters' => $availableFilters,
        ]);
    }

    private function resolveHouse($user): string
    {
        if (! empty($user->legislative_house)) {
            return $user->legislative_house;
        }

        $role = $user->role instanceof UserRole
            ? $user->role
            : UserRole::from($user->role);

        return $role === UserRole::Senator ? 'senate' : 'national_assembly';
    }
}

<?php

namespace App\Http\Controllers\Api\Legislator;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\LegislatorBillResource;
use App\Http\Resources\LegislatorHighlightResource;
use App\Http\Resources\SubmissionResource;
use App\Models\Bill;
use App\Models\LegislatorHighlight;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $house = $this->resolveHouse($user);
        $filters = $request->only(['status', 'search']);

        $query = Bill::query()
            ->withCount([
                'submissions',
                'submissions as pending_submissions_count' => fn ($builder) => $builder->where('status', 'pending'),
                'submissions as aggregated_submissions_count' => fn ($builder) => $builder->where('status', 'aggregated'),
                'highlights as highlights_count' => fn ($builder) => $builder->where('user_id', $user->id),
            ])
            ->where(function (Builder $builder) use ($house) {
                $builder->where('house', $house)
                    ->orWhere('house', 'both');
            })
            ->when($filters['status'] ?? null, function (Builder $builder, string $status) {
                return match ($status) {
                    'open' => $builder->where('status', 'open_for_participation'),
                    'closed' => $builder->where('status', 'closed'),
                    'draft' => $builder->where('status', 'draft'),
                    default => $builder,
                };
            })
            ->when($filters['search'] ?? null, function (Builder $builder, string $search) {
                $builder->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('bill_number', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('participation_end_date');

        $bills = $query->paginate(12)->withQueryString();

        $metricsQuery = Bill::query()
            ->where(function (Builder $builder) use ($house) {
                $builder->where('house', $house)
                    ->orWhere('house', 'both');
            });

        $metrics = [
            'open' => (clone $metricsQuery)->where('status', 'open_for_participation')->count(),
            'closingSoon' => (clone $metricsQuery)
                ->where('status', 'open_for_participation')
                ->whereBetween('participation_end_date', [Carbon::now(), Carbon::now()->addDays(7)])
                ->count(),
            'recentlyClosed' => (clone $metricsQuery)
                ->where('status', 'closed')
                ->whereBetween('updated_at', [Carbon::now()->subDays(14), Carbon::now()])
                ->count(),
            'highlights' => LegislatorHighlight::query()->where('user_id', $user->id)->count(),
        ];

        return LegislatorBillResource::collection($bills)
            ->additional([
                'metrics' => $metrics,
            ])
            ->response();
    }

    public function show(Request $request, Bill $bill): JsonResponse
    {
        $user = $request->user();
        $house = $this->resolveHouse($user);

        abort_unless(in_array($bill->house, [$house, 'both'], true), 403);

        $bill->loadMissing('summary');

        $filters = $request->only(['status', 'type', 'county']);

        $submissionsQuery = Submission::query()
            ->with(['user:id,name,email', 'bill:id,title'])
            ->where('bill_id', $bill->id)
            ->orderByDesc('created_at');

        $submissions = (clone $submissionsQuery)
            ->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($filters['type'] ?? null, fn (Builder $builder, string $type) => $builder->where('submission_type', $type))
            ->when($filters['county'] ?? null, fn (Builder $builder, string $county) => $builder->where('submitter_county', $county))
            ->paginate(20)
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

        return response()->json([
            'bill' => LegislatorBillResource::make($bill)->resolve($request),
            'submissions' => SubmissionResource::collection($submissions)->response()->getData(true),
            'aggregation' => $aggregation,
            'highlights' => LegislatorHighlightResource::collection($highlights)->resolve($request),
            'filters' => $filters,
        ]);
    }

    private function resolveHouse($user): string
    {
        if (! empty($user->legislative_house)) {
            return $user->legislative_house;
        }

        $role = $user->role instanceof UserRole ? $user->role : UserRole::from($user->role);

        return $role === UserRole::Senator ? 'senate' : 'national_assembly';
    }
}

<?php

namespace App\Http\Controllers\Api\Clerk;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clerk\UpdateCitizenStatusRequest;
use App\Http\Resources\CitizenResource;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitizenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'county', 'search']);

        $query = User::query()
            ->where('role', UserRole::Citizen->value)
            ->withCount('submissions')
            ->when($filters['status'] ?? null, function (Builder $builder, string $status) {
                return match ($status) {
                    'verified' => $builder->where('is_verified', true),
                    'unverified' => $builder->where('is_verified', false),
                    'suspended' => $builder->whereNotNull('suspended_at'),
                    default => $builder,
                };
            })
            ->when($filters['county'] ?? null, fn (Builder $builder, string $county) => $builder->where('county', $county))
            ->when($filters['search'] ?? null, function (Builder $builder, string $search) {
                $builder->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('id_number', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('created_at');

        $citizens = $query->paginate(30)->withQueryString();

        $metricsBase = User::query()->where('role', UserRole::Citizen->value);

        $metrics = [
            'total' => (clone $metricsBase)->count(),
            'verified' => (clone $metricsBase)->where('is_verified', true)->count(),
            'unverified' => (clone $metricsBase)->where('is_verified', false)->count(),
            'suspended' => (clone $metricsBase)->whereNotNull('suspended_at')->count(),
        ];

        $counties = (clone $metricsBase)
            ->whereNotNull('county')
            ->distinct()
            ->orderBy('county')
            ->pluck('county')
            ->values();

        $recentSubmissions = Submission::query()
            ->with(['user:id,name,email', 'bill:id,title'])
            ->whereIn('user_id', (clone $metricsBase)->select('id'))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Submission $submission) => [
                'id' => $submission->id,
                'tracking_id' => $submission->tracking_id,
                'submission_type' => $submission->submission_type,
                'created_at' => $submission->created_at,
                'bill' => [
                    'id' => $submission->bill?->id,
                    'title' => $submission->bill?->title,
                ],
                'citizen' => $submission->user?->only(['id', 'name', 'email']),
            ]);

        return CitizenResource::collection($citizens)
            ->additional([
                'metrics' => $metrics,
                'counties' => $counties,
                'recentSubmissions' => $recentSubmissions,
            ])
            ->response();
    }

    public function update(UpdateCitizenStatusRequest $request, User $citizen): JsonResponse
    {
        $role = $citizen->role instanceof UserRole ? $citizen->role->value : $citizen->role;
        abort_unless($role === UserRole::Citizen->value, 404);

        $action = $request->validated('action');

        match ($action) {
            'verify' => $citizen->forceFill([
                'is_verified' => true,
                'suspended_at' => null,
            ])->save(),
            'unverify' => $citizen->forceFill([
                'is_verified' => false,
            ])->save(),
            'suspend' => $citizen->forceFill([
                'suspended_at' => now(),
            ])->save(),
            'restore' => $citizen->forceFill([
                'suspended_at' => null,
            ])->save(),
            default => null,
        };

        return CitizenResource::make($citizen->fresh())
            ->response();
    }
}

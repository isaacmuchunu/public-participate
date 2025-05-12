<?php

namespace App\Http\Controllers\Clerk;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clerk\UpdateCitizenStatusRequest;
use App\Http\Resources\CitizenResource;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CitizenController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'county', 'search']);

        $baseQuery = User::query()
            ->where('role', 'citizen');

        $citizens = (clone $baseQuery)
            ->withCount('submissions')
            ->when($filters['status'] ?? null, function (Builder $query, string $status) {
                return match ($status) {
                    'verified' => $query->where('is_verified', true),
                    'unverified' => $query->where('is_verified', false),
                    'suspended' => $query->whereNotNull('suspended_at'),
                    default => $query,
                };
            })
            ->when($filters['county'] ?? null, fn (Builder $query, string $county) => $query->where('county', $county))
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('id_number', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $metrics = [
            'total' => (clone $baseQuery)->count(),
            'verified' => (clone $baseQuery)->where('is_verified', true)->count(),
            'unverified' => (clone $baseQuery)->where('is_verified', false)->count(),
            'suspended' => (clone $baseQuery)->whereNotNull('suspended_at')->count(),
        ];

        $recentSubmissions = Submission::query()
            ->with(['user:id,name,email', 'bill:id,title'])
            ->whereIn('user_id', (clone $baseQuery)->select('id'))
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

        $counties = (clone $baseQuery)
            ->whereNotNull('county')
            ->distinct()
            ->orderBy('county')
            ->pluck('county')
            ->values();

        return Inertia::render('Clerk/Citizens/Index', [
            'citizens' => CitizenResource::collection($citizens),
            'filters' => $filters,
            'metrics' => $metrics,
            'counties' => $counties,
            'recentSubmissions' => $recentSubmissions,
        ]);
    }

    public function update(UpdateCitizenStatusRequest $request, User $citizen): RedirectResponse
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

        return redirect()
            ->route('clerk.citizens.index')
            ->with('flash', [
                'status' => 'success',
                'message' => 'Citizen status updated successfully.',
            ]);
    }
}

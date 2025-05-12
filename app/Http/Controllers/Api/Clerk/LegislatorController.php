<?php

namespace App\Http\Controllers\Api\Clerk;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clerk\StoreLegislatorRequest;
use App\Http\Requests\Clerk\UpdateLegislatorRequest;
use App\Http\Resources\LegislatorResource;
use App\Jobs\SendLegislatorInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class LegislatorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['house', 'status', 'search']);

        $query = User::query()
            ->with('inviter')
            ->whereIn('role', [UserRole::Mp->value, UserRole::Senator->value])
            ->when($filters['house'] ?? null, fn (Builder $builder, string $house) => $builder->where('legislative_house', $house))
            ->when($filters['status'] ?? null, function (Builder $builder, string $status) {
                return match ($status) {
                    'active' => $builder->whereNull('suspended_at')->whereNotNull('email_verified_at'),
                    'pending' => $builder->whereNull('suspended_at')->whereNull('email_verified_at'),
                    'suspended' => $builder->whereNotNull('suspended_at'),
                    'expired' => $builder->whereNull('email_verified_at')->whereNotNull('invitation_expires_at')->where('invitation_expires_at', '<', now()),
                    default => $builder,
                };
            })
            ->when($filters['search'] ?? null, function (Builder $builder, string $search) {
                $builder->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('constituency', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name');

        $legislators = $query->paginate(20)->withQueryString();

        $metricsBase = User::query()->whereIn('role', [UserRole::Mp->value, UserRole::Senator->value]);

        $metrics = [
            'total' => (clone $metricsBase)->count(),
            'active' => (clone $metricsBase)->whereNull('suspended_at')->whereNotNull('email_verified_at')->count(),
            'pending' => (clone $metricsBase)->whereNull('suspended_at')->whereNull('email_verified_at')->count(),
            'suspended' => (clone $metricsBase)->whereNotNull('suspended_at')->count(),
        ];

        return LegislatorResource::collection($legislators)
            ->additional([
                'metrics' => $metrics,
            ])
            ->response();
    }

    public function store(StoreLegislatorRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $legislator = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'county' => $validated['county'] ?? null,
            'constituency' => $validated['constituency'] ?? null,
            'role' => $validated['legislative_house'] === 'senate' ? UserRole::Senator->value : UserRole::Mp->value,
            'legislative_house' => $validated['legislative_house'],
            'password' => Str::random(32),
            'invited_by' => $request->user()->id,
            'invited_at' => now(),
            'invitation_expires_at' => Carbon::now()->addDays($validated['expires_in_days'] ?? 7),
            'invitation_token' => (string) Str::uuid(),
        ]);

        SendLegislatorInvitation::dispatch($legislator, $request->user(), $validated['invitation_message'] ?? null);

        return LegislatorResource::make($legislator->fresh('inviter'))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateLegislatorRequest $request, User $legislator): JsonResponse
    {
        $role = $legislator->role instanceof UserRole ? $legislator->role->value : $legislator->role;
        abort_unless(in_array($role, [UserRole::Mp->value, UserRole::Senator->value], true), 404);

        $validated = $request->validated();

        foreach (['name', 'email', 'phone', 'county', 'constituency'] as $field) {
            if (array_key_exists($field, $validated)) {
                $legislator->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('legislative_house', $validated)) {
            $legislator->legislative_house = $validated['legislative_house'];
            $legislator->role = $validated['legislative_house'] === 'senate' ? UserRole::Senator : UserRole::Mp;
        }

        if (array_key_exists('suspended', $validated)) {
            $legislator->suspended_at = $validated['suspended'] ? now() : null;
        }

        $legislator->save();

        if (! empty($validated['reset_invitation'])) {
            $legislator->forceFill([
                'invitation_token' => (string) Str::uuid(),
                'invited_at' => now(),
                'invitation_expires_at' => Carbon::now()->addDays($request->integer('expires_in_days', 7)),
            ])->save();

            SendLegislatorInvitation::dispatch($legislator, $request->user(), $request->input('invitation_message'));
        }

        return LegislatorResource::make($legislator->fresh('inviter'))
            ->response();
    }

    public function destroy(User $legislator): JsonResponse
    {
        $role = $legislator->role instanceof UserRole ? $legislator->role->value : $legislator->role;
        abort_unless(in_array($role, [UserRole::Mp->value, UserRole::Senator->value], true), 404);

        $legislator->update([
            'suspended_at' => now(),
        ]);

        return LegislatorResource::make($legislator->fresh())
            ->response();
    }

    public function restore(User $legislator): JsonResponse
    {
        $role = $legislator->role instanceof UserRole ? $legislator->role->value : $legislator->role;
        abort_unless(in_array($role, [UserRole::Mp->value, UserRole::Senator->value], true), 404);

        $legislator->update([
            'suspended_at' => null,
        ]);

        return LegislatorResource::make($legislator->fresh())
            ->response();
    }

    public function resend(Request $request, User $legislator): JsonResponse
    {
        $role = $legislator->role instanceof UserRole ? $legislator->role->value : $legislator->role;
        abort_unless(in_array($role, [UserRole::Mp->value, UserRole::Senator->value], true), 404);

        $legislator->forceFill([
            'invitation_token' => $legislator->invitation_token ?? (string) Str::uuid(),
            'invited_at' => now(),
            'invitation_expires_at' => Carbon::now()->addDays($request->integer('expires_in_days', 7)),
        ])->save();

        SendLegislatorInvitation::dispatch($legislator, $request->user(), $request->input('invitation_message'));

        return LegislatorResource::make($legislator->fresh('inviter'))
            ->response();
    }
}

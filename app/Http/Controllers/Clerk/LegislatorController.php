<?php

namespace App\Http\Controllers\Clerk;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clerk\StoreLegislatorRequest;
use App\Http\Requests\Clerk\UpdateLegislatorRequest;
use App\Http\Resources\LegislatorResource;
use App\Jobs\SendLegislatorInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LegislatorController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['house', 'status', 'search']);

        $baseQuery = User::query()
            ->with('inviter')
            ->whereIn('role', [UserRole::Mp->value, UserRole::Senator->value]);

        $legislators = (clone $baseQuery)
            ->when($filters['house'] ?? null, fn (Builder $query, string $house) => $query->where('legislative_house', $house))
            ->when($filters['status'] ?? null, function (Builder $query, string $status) {
                return match ($status) {
                    'active' => $query->whereNull('suspended_at')->whereNotNull('email_verified_at'),
                    'pending' => $query->whereNull('suspended_at')->whereNull('email_verified_at'),
                    'suspended' => $query->whereNotNull('suspended_at'),
                    'expired' => $query->whereNull('email_verified_at')->whereNotNull('invitation_expires_at')->where('invitation_expires_at', '<', now()),
                    default => $query,
                };
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('constituency', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $metrics = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->whereNull('suspended_at')->whereNotNull('email_verified_at')->count(),
            'pending' => (clone $baseQuery)->whereNull('suspended_at')->whereNull('email_verified_at')->count(),
            'suspended' => (clone $baseQuery)->whereNotNull('suspended_at')->count(),
        ];

        $expiringSoon = (clone $baseQuery)
            ->whereNull('email_verified_at')
            ->whereNotNull('invitation_expires_at')
            ->whereBetween('invitation_expires_at', [now(), now()->addDays(3)])
            ->count();

        return Inertia::render('Clerk/Legislators/Index', [
            'legislators' => LegislatorResource::collection($legislators),
            'filters' => $filters,
            'metrics' => array_merge($metrics, ['expiring' => $expiringSoon]),
        ]);
    }

    public function store(StoreLegislatorRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $token = (string) Str::uuid();
        $expiresAt = Carbon::now()->addDays($validated['expires_in_days'] ?? 7);

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
            'invitation_expires_at' => $expiresAt,
            'invitation_token' => $token,
        ]);

        SendLegislatorInvitation::dispatch($legislator, $request->user(), $validated['invitation_message'] ?? null);

        return redirect()
            ->route('clerk.legislators.index')
            ->with('flash', [
                'status' => 'success',
                'message' => 'Invitation sent to '.$legislator->name.'.',
            ]);
    }

    public function update(UpdateLegislatorRequest $request, User $legislator): RedirectResponse
    {
        $role = $legislator->role instanceof UserRole ? $legislator->role->value : $legislator->role;

        abort_unless(in_array($role, [UserRole::Mp->value, UserRole::Senator->value], true), 404);

        $validated = $request->validated();

        foreach (['name', 'email', 'phone', 'county', 'constituency'] as $attribute) {
            if (array_key_exists($attribute, $validated)) {
                $legislator->{$attribute} = $validated[$attribute];
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

        return redirect()
            ->route('clerk.legislators.index')
            ->with('flash', [
                'status' => 'success',
                'message' => $legislator->name.' has been updated.',
            ]);
    }

    public function destroy(User $legislator): RedirectResponse
    {
        $role = $legislator->role instanceof UserRole ? $legislator->role->value : $legislator->role;

        abort_unless(in_array($role, [UserRole::Mp->value, UserRole::Senator->value], true), 404);

        $legislator->update([
            'suspended_at' => now(),
        ]);

        return redirect()
            ->route('clerk.legislators.index')
            ->with('flash', [
                'status' => 'success',
                'message' => $legislator->name.' has been suspended.',
            ]);
    }

    public function restore(User $legislator): RedirectResponse
    {
        $role = $legislator->role instanceof UserRole ? $legislator->role->value : $legislator->role;

        abort_unless(in_array($role, [UserRole::Mp->value, UserRole::Senator->value], true), 404);

        $legislator->update([
            'suspended_at' => null,
        ]);

        return redirect()
            ->route('clerk.legislators.index')
            ->with('flash', [
                'status' => 'success',
                'message' => $legislator->name.' has been reinstated.',
            ]);
    }

    public function resendInvitation(Request $request, User $legislator): RedirectResponse
    {
        abort_unless(in_array($legislator->role, ['mp', 'senator'], true), 404);

        $legislator->forceFill([
            'invitation_token' => $legislator->invitation_token ?? (string) Str::uuid(),
            'invited_at' => now(),
            'invitation_expires_at' => Carbon::now()->addDays($request->integer('expires_in_days', 7)),
        ])->save();

        SendLegislatorInvitation::dispatch($legislator, $request->user(), $request->input('invitation_message'));

        return redirect()
            ->route('clerk.legislators.index')
            ->with('flash', [
                'status' => 'success',
                'message' => 'Invitation resent to '.$legislator->name.'.',
            ]);
    }
}

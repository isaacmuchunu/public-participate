<?php

namespace App\Http\Controllers\Legislator;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Legislator\StoreHighlightRequest;
use App\Models\Bill;
use App\Models\LegislatorHighlight;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HighlightController extends Controller
{
    public function store(StoreHighlightRequest $request, Bill $bill): RedirectResponse
    {
        $user = $request->user();
        $house = $this->resolveHouse($user);

        abort_unless(in_array($bill->house, [$house, 'both'], true), 403);

        $validated = $request->validated();

        $submission = null;
        if (! empty($validated['submission_id'])) {
            $submission = Submission::query()
                ->where('bill_id', $bill->id)
                ->where('id', $validated['submission_id'])
                ->firstOrFail();
        }

        $payload = [
            'title' => $validated['title'],
            'clause_reference' => $validated['clause_reference'] ?? null,
            'excerpt' => $validated['excerpt'] ?? ($submission?->content ? Str::limit($submission->content, 280) : null),
            'note' => $validated['note'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'highlighted_at' => now(),
        ];

        if (! empty($validated['submission_id'])) {
            LegislatorHighlight::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'bill_id' => $bill->id,
                    'submission_id' => $validated['submission_id'],
                ],
                $payload
            );
        } else {
            LegislatorHighlight::create(
                $payload + [
                    'user_id' => $user->id,
                    'bill_id' => $bill->id,
                    'submission_id' => null,
                ]
            );
        }

        return back()->with('flash', [
            'status' => 'success',
            'message' => 'Highlight saved.',
        ]);
    }

    public function destroy(Request $request, LegislatorHighlight $highlight): RedirectResponse
    {
        $user = $request->user();

        abort_unless($highlight->user_id === $user->id || $user->isAdmin(), 403);

        $highlight->delete();

        return back()->with('flash', [
            'status' => 'success',
            'message' => 'Highlight removed.',
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

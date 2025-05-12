<?php

namespace App\Http\Controllers\Api\Legislator;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Legislator\StoreHighlightRequest;
use App\Http\Resources\LegislatorHighlightResource;
use App\Models\Bill;
use App\Models\LegislatorHighlight;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HighlightController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = LegislatorHighlight::query()
            ->with(['bill:id,title,house', 'submission:id,tracking_id,submission_type,content,submitter_name'])
            ->where('user_id', $user->id)
            ->when($request->integer('bill_id'), fn (Builder $builder, int $billId) => $builder->where('bill_id', $billId))
            ->orderByDesc('highlighted_at');

        $highlights = $query->paginate(25)->withQueryString();

        return LegislatorHighlightResource::collection($highlights)
            ->response();
    }

    public function store(StoreHighlightRequest $request, Bill $bill): JsonResponse
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

        $highlight = ! empty($validated['submission_id'])
            ? LegislatorHighlight::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'bill_id' => $bill->id,
                    'submission_id' => $validated['submission_id'],
                ],
                $payload
            )
            : LegislatorHighlight::create(
                $payload + [
                    'user_id' => $user->id,
                    'bill_id' => $bill->id,
                    'submission_id' => null,
                ]
            );

        return LegislatorHighlightResource::make($highlight->fresh(['bill', 'submission']))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, LegislatorHighlight $highlight): JsonResponse
    {
        $user = $request->user();

        abort_unless($highlight->user_id === $user->id || $user->isAdmin(), 403);

        $highlight->delete();

        return response()->json(null, 204);
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

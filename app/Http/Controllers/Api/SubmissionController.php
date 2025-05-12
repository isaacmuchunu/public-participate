<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Submission\StoreSubmissionRequest;
use App\Http\Requests\Submission\UpdateSubmissionStatusRequest;
use App\Http\Resources\SubmissionResource;
use App\Models\Bill;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Submission::class);

        $query = Submission::query()
            ->with(['bill.summary', 'user', 'reviewer'])
            ->latest('created_at');

        $user = $request->user();

        if ($user?->role === 'citizen') {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('bill_id')) {
            $query->where('bill_id', $request->integer('bill_id'));
        }

        $submissions = $query->paginate();

        return SubmissionResource::collection($submissions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubmissionRequest $request): SubmissionResource
    {
        $validated = $request->validated();

        $bill = Bill::findOrFail($validated['bill_id']);

        if (! $bill->isOpenForParticipation()) {
            abort(422, 'Submissions are closed for this bill.');
        }

        $user = $request->user();

        $validated['user_id'] = $user->id;
        $validated['submitter_name'] = $validated['submitter_name'] ?? $user->name;
        $validated['submitter_phone'] = $validated['submitter_phone'] ?? $user->phone;
        $validated['submitter_email'] = $validated['submitter_email'] ?? $user->email;
        $validated['submitter_county'] = $validated['submitter_county'] ?? $user->county;
        $validated['channel'] = 'api';

        $submission = Submission::create($validated);

        $bill->increment('submissions_count');

        return SubmissionResource::make($submission->load(['bill.summary', 'user']));
    }

    /**
     * Display the specified resource.
     */
    public function show(Submission $submission): SubmissionResource
    {
        $this->authorize('view', $submission);

        return SubmissionResource::make($submission->load(['bill.summary', 'user', 'reviewer']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubmissionStatusRequest $request, Submission $submission): SubmissionResource
    {
        $validated = $request->validated();

        $validated['reviewed_at'] = now();
        $validated['reviewed_by'] = $request->user()->id;

        $submission->update($validated);

        return SubmissionResource::make($submission->fresh(['bill.summary', 'user', 'reviewer']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Submission $submission): Response
    {
        $this->authorize('delete', $submission);

        $submission->delete();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Submission\StoreSubmissionDraftRequest;
use App\Http\Requests\Submission\UpdateSubmissionDraftRequest;
use App\Models\SubmissionDraft;
use App\Services\SubmissionWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionDraftController extends Controller
{
    public function __construct(
        protected SubmissionWorkflowService $workflowService
    ) {
        //
    }

    /**
     * Store a new submission draft
     */
    public function store(StoreSubmissionDraftRequest $request): JsonResponse
    {
        $draft = SubmissionDraft::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Draft saved successfully',
            'draft' => $draft->load('bill'),
        ], 201);
    }

    /**
     * Update an existing draft
     */
    public function update(UpdateSubmissionDraftRequest $request, SubmissionDraft $draft): JsonResponse
    {
        $this->authorize('update', $draft);

        $draft->update($request->validated());

        return response()->json([
            'message' => 'Draft updated successfully',
            'draft' => $draft->fresh(['bill']),
        ]);
    }

    /**
     * Delete a draft
     */
    public function destroy(Request $request, SubmissionDraft $draft): JsonResponse
    {
        $this->authorize('delete', $draft);

        $draft->delete();

        return response()->json([
            'message' => 'Draft deleted successfully',
        ]);
    }

    /**
     * Submit a draft as a formal submission
     */
    public function submit(Request $request, SubmissionDraft $draft): JsonResponse
    {
        $this->authorize('submit', $draft);

        try {
            $submission = $this->workflowService->submitDraft($draft);

            return response()->json([
                'message' => 'Submission created successfully',
                'submission' => $submission->load(['bill', 'user']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to submit draft',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}

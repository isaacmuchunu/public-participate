<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Engagement\StoreEngagementRequest;
use App\Models\CitizenEngagement;
use App\Services\EngagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EngagementController extends Controller
{
    public function __construct(
        protected EngagementService $engagementService
    ) {
        //
    }

    /**
     * Get user's engagements
     */
    public function index(Request $request): JsonResponse
    {
        $engagements = $this->engagementService->getUserEngagements(
            $request->user(),
            $request->only(['bill_id', 'status', 'per_page'])
        );

        return response()->json($engagements);
    }

    /**
     * Send a new engagement message
     */
    public function store(StoreEngagementRequest $request): JsonResponse
    {
        try {
            $engagement = $this->engagementService->sendMessage(
                $request->user(),
                $request->getRecipient(),
                $request->getBill(),
                $request->getSubmission(),
                $request->validated('subject'),
                $request->validated('message')
            );

            return response()->json([
                'message' => 'Message sent successfully',
                'engagement' => $engagement->load(['bill', 'recipient', 'submission']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mark engagement as read
     */
    public function markAsRead(Request $request, CitizenEngagement $engagement): JsonResponse
    {
        try {
            $this->engagementService->markAsRead($engagement, $request->user());

            return response()->json([
                'message' => 'Message marked as read',
                'engagement' => $engagement->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark as read',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}

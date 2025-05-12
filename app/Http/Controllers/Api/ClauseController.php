<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillClause;
use App\Services\Bill\BillClauseParsingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClauseController extends Controller
{
    public function __construct(
        protected BillClauseParsingService $clauseService
    ) {}

    /**
     * Get all clauses for a bill
     */
    public function index(Bill $bill): JsonResponse
    {
        $clauses = $bill->topLevelClauses()
            ->with(['children', 'analytics'])
            ->withCount('submissions')
            ->get();

        return response()->json([
            'data' => $clauses,
        ]);
    }

    /**
     * Get a specific clause
     */
    public function show(Bill $bill, BillClause $clause): JsonResponse
    {
        // Ensure clause belongs to this bill
        if ($clause->bill_id !== $bill->id) {
            return response()->json([
                'message' => 'Clause does not belong to this bill',
            ], 404);
        }

        $clause->load(['children', 'parent', 'analytics', 'submissions' => function ($query) {
            $query->latest()->limit(10);
        }]);

        $clause->loadCount('submissions');

        return response()->json([
            'data' => $clause,
        ]);
    }

    /**
     * Parse PDF and generate clauses
     */
    public function parse(Request $request, Bill $bill): JsonResponse
    {
        try {
            $clauses = $this->clauseService->parseBillClauses($bill);

            return response()->json([
                'message' => 'Bill clauses parsed successfully',
                'data' => $clauses,
                'count' => $clauses->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Clause parsing failed', [
                'bill_id' => $bill->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to parse bill clauses',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually create a clause
     */
    public function store(Request $request, Bill $bill): JsonResponse
    {
        $validated = $request->validate([
            'clause_number' => 'required|string|max:255',
            'clause_type' => 'required|in:section,subsection,paragraph,subparagraph',
            'parent_clause_id' => 'nullable|exists:bill_clauses,id',
            'title' => 'nullable|string',
            'content' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        $clause = $this->clauseService->addClause($bill, $validated);

        return response()->json([
            'message' => 'Clause created successfully',
            'data' => $clause,
        ], 201);
    }

    /**
     * Update a clause
     */
    public function update(Request $request, Bill $bill, BillClause $clause): JsonResponse
    {
        // Ensure clause belongs to this bill
        if ($clause->bill_id !== $bill->id) {
            return response()->json([
                'message' => 'Clause does not belong to this bill',
            ], 404);
        }

        $validated = $request->validate([
            'clause_number' => 'sometimes|string|max:255',
            'clause_type' => 'sometimes|in:section,subsection,paragraph,subparagraph',
            'parent_clause_id' => 'nullable|exists:bill_clauses,id',
            'title' => 'nullable|string',
            'content' => 'sometimes|string',
            'metadata' => 'nullable|array',
        ]);

        $clause = $this->clauseService->updateClause($clause, $validated);

        return response()->json([
            'message' => 'Clause updated successfully',
            'data' => $clause,
        ]);
    }

    /**
     * Delete a clause
     */
    public function destroy(Bill $bill, BillClause $clause): JsonResponse
    {
        // Ensure clause belongs to this bill
        if ($clause->bill_id !== $bill->id) {
            return response()->json([
                'message' => 'Clause does not belong to this bill',
            ], 404);
        }

        $this->clauseService->deleteClause($clause);

        return response()->json([
            'message' => 'Clause deleted successfully',
        ]);
    }
}

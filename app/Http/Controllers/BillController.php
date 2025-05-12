<?php

namespace App\Http\Controllers;

use App\Http\Requests\Bill\StoreBillRequest;
use App\Http\Requests\Bill\UpdateBillRequest;
use App\Models\Bill;
use App\Models\BillSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Bill::with(['creator', 'summary'])
            ->withCount('submissions');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by house
        if ($request->has('house') && $request->house !== 'all') {
            $query->where('house', $request->house);
        }

        // Filter by tag
        if ($request->has('tag') && $request->tag !== 'all') {
            $query->byTag($request->tag);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('bill_number', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $bills = $query->orderBy('created_at', 'desc')->paginate(12);

        return Inertia::render('Bills/Index', [
            'bills' => $bills,
            'filters' => $request->only(['status', 'house', 'tag', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Bill::class);

        return Inertia::render('Bills/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBillRequest $request)
    {
        $this->authorize('create', Bill::class);

        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        // Handle PDF upload
        if ($request->hasFile('pdf_file')) {
            $path = $request->file('pdf_file')->store('bills', 'public');
            $validated['pdf_path'] = $path;
        }

        $bill = Bill::create($validated);

        return redirect()->route('bills.show', $bill)
            ->with('success', 'Bill created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Bill $bill)
    {
        $bill->load(['creator', 'summary', 'submissions' => function ($query) {
            $query->with('user')->latest()->take(10);
        }]);

        // Increment view count
        $bill->increment('views_count');

        return Inertia::render('Bills/Show', [
            'bill' => $bill,
            'canEdit' => Auth::user()?->can('update', $bill),
            'canDelete' => Auth::user()?->can('delete', $bill),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bill $bill)
    {
        $this->authorize('update', $bill);

        return Inertia::render('Bills/Edit', [
            'bill' => $bill,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBillRequest $request, Bill $bill)
    {
        $this->authorize('update', $bill);

        $validated = $request->validated();

        // Handle PDF upload
        if ($request->hasFile('pdf_file')) {
            // Delete old PDF if exists
            if ($bill->pdf_path) {
                Storage::disk('public')->delete($bill->pdf_path);
            }
            $path = $request->file('pdf_file')->store('bills', 'public');
            $validated['pdf_path'] = $path;
        }

        $bill->update($validated);

        return redirect()->route('bills.show', $bill)
            ->with('success', 'Bill updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bill $bill)
    {
        $this->authorize('delete', $bill);

        // Delete PDF file if exists
        if ($bill->pdf_path) {
            Storage::disk('public')->delete($bill->pdf_path);
        }

        $bill->delete();

        return redirect()->route('bills.index')
            ->with('success', 'Bill deleted successfully.');
    }

    /**
     * Show bills open for public participation
     */
    public function participate(Request $request)
    {
        $query = Bill::openForParticipation()
            ->with(['summary'])
            ->withCount('submissions');

        // Filter by tag
        if ($request->has('tag') && $request->tag !== 'all') {
            $query->byTag($request->tag);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $bills = $query->orderBy('participation_end_date', 'asc')->paginate(12);

        return Inertia::render('Bills/Participate', [
            'bills' => $bills,
            'filters' => $request->only(['tag', 'search']),
        ]);
    }

    /**
     * Generate or update AI summary for a bill
     */
    public function generateSummary(Bill $bill)
    {
        $this->authorize('update', $bill);

        // This would integrate with an AI service like OpenAI
        // For now, we'll create a placeholder summary
        $summary = BillSummary::updateOrCreate(
            ['bill_id' => $bill->id],
            [
                'simplified_summary_en' => 'This is a simplified summary of the bill that explains its key provisions in plain language.',
                'simplified_summary_sw' => 'Hii ni muhtasari rahisi wa mswada ambao unaelezea masharti yake muhimu kwa lugha rahisi.',
                'key_clauses' => [
                    'Main provision 1: Description of what this clause does',
                    'Main provision 2: Description of what this clause does',
                    'Impact: How this bill will affect citizens',
                ],
                'generation_method' => 'ai',
                'generated_at' => now(),
            ]
        );

        return redirect()->route('bills.show', $bill)
            ->with('success', 'Summary generated successfully.');
    }
}

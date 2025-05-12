<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bill\StoreBillRequest;
use App\Http\Requests\Bill\UpdateBillRequest;
use App\Http\Resources\BillResource;
use App\Models\Bill;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class BillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Bill::class);

        $bills = Bill::query()
            ->with('summary')
            ->latest('created_at')
            ->paginate();

        return BillResource::collection($bills);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBillRequest $request): BillResource
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        if ($request->hasFile('pdf_file')) {
            $validated['pdf_path'] = $request->file('pdf_file')->store('bills', 'public');
        }

        $bill = Bill::create($validated);

        return BillResource::make($bill);
    }

    /**
     * Display the specified resource.
     */
    public function show(Bill $bill): BillResource
    {
        $this->authorize('view', $bill);

        return BillResource::make($bill->load('summary'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBillRequest $request, Bill $bill): BillResource
    {
        $validated = $request->validated();

        if ($request->hasFile('pdf_file')) {
            if ($bill->pdf_path) {
                Storage::disk('public')->delete($bill->pdf_path);
            }

            $validated['pdf_path'] = $request->file('pdf_file')->store('bills', 'public');
        }

        $bill->update($validated);

        return BillResource::make($bill->fresh('summary'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bill $bill): Response
    {
        $this->authorize('delete', $bill);

        if ($bill->pdf_path) {
            Storage::disk('public')->delete($bill->pdf_path);
        }

        $bill->delete();

        return response()->noContent();
    }
}

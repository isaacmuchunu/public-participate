<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Submission\StoreSubmissionRequest;
use App\Http\Requests\Submission\UpdateSubmissionStatusRequest;
use App\Http\Resources\SubmissionDraftResource;
use App\Models\Bill;
use App\Models\Submission;
use App\Models\SubmissionDraft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SubmissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Submission::with(['bill', 'user', 'reviewer'])
            ->when($user->isCitizen(), function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->when($user->isLegislator(), function ($q) use ($user) {
                $house = $this->resolveLegislativeHouse($user);

                $q->whereHas('bill', function ($billQuery) use ($house) {
                    $billQuery->whereIn('house', [$house, 'both']);
                });
            });

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by submission type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('submission_type', $request->type);
        }

        // Filter by bill
        if ($request->has('bill_id')) {
            $query->where('bill_id', $request->bill_id);
        }

        $submissions = $query->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('Submissions/Index', [
            'submissions' => $submissions,
            'filters' => $request->only(['status', 'type', 'bill_id']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $bill = null;
        if ($request->has('bill_id')) {
            $bill = Bill::findOrFail($request->bill_id);

            // Check if bill is open for participation
            if (! $bill->isOpenForParticipation()) {
                return redirect()->route('bills.show', $bill)
                    ->with('error', 'This bill is not currently open for public participation.');
            }
        }

        $drafts = SubmissionDraft::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('submitted_at')
            ->with(['bill:id,title,bill_number,status,participation_end_date'])
            ->latest('updated_at')
            ->get();

        $activeDraft = null;

        if ($request->filled('draft_id')) {
            $activeDraft = $drafts->firstWhere('id', (int) $request->query('draft_id'));

            if (! $activeDraft) {
                $activeDraft = SubmissionDraft::query()
                    ->where('id', $request->query('draft_id'))
                    ->where('user_id', $request->user()->id)
                    ->whereNull('submitted_at')
                    ->with(['bill:id,title,bill_number,status,participation_end_date'])
                    ->first();

                if ($activeDraft) {
                    $drafts->push($activeDraft);
                    $drafts = $drafts->sortByDesc('updated_at');
                }
            }
        }

        $recentSubmissions = Submission::query()
            ->where('user_id', $request->user()->id)
            ->with(['bill:id,title,bill_number,status'])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn (Submission $submission) => [
                'id' => $submission->id,
                'tracking_id' => $submission->tracking_id,
                'status' => $submission->status,
                'created_at' => $submission->created_at?->toDateTimeString(),
                'bill' => $submission->bill ? [
                    'id' => $submission->bill->id,
                    'title' => $submission->bill->title,
                    'bill_number' => $submission->bill->bill_number,
                ] : null,
            ]);

        return Inertia::render('Submissions/Create', [
            'bill' => $bill,
            'drafts' => SubmissionDraftResource::collection($drafts)->resolve(),
            'activeDraft' => $activeDraft ? SubmissionDraftResource::make($activeDraft)->resolve() : null,
            'recentSubmissions' => $recentSubmissions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubmissionRequest $request)
    {
        $validated = $request->validated();

        $draftId = $validated['draft_id'] ?? null;
        unset($validated['draft_id']);

        $bill = Bill::findOrFail($validated['bill_id']);

        // Check if bill is open for participation
        if (! $bill->isOpenForParticipation()) {
            return back()->with('error', 'This bill is not currently open for public participation.');
        }

        // Set user information
        if (Auth::check()) {
            $validated['user_id'] = Auth::id();
            $validated['submitter_name'] = $validated['submitter_name'] ?? Auth::user()->name;
            $validated['submitter_phone'] = $validated['submitter_phone'] ?? Auth::user()->phone;
            $validated['submitter_email'] = $validated['submitter_email'] ?? Auth::user()->email;
            $validated['submitter_county'] = $validated['submitter_county'] ?? Auth::user()->county;
        }

        $validated['channel'] = 'web';

        $submission = Submission::create($validated);

        // Update bill submissions count
        $bill->increment('submissions_count');

        if ($draftId) {
            SubmissionDraft::query()
                ->where('id', $draftId)
                ->where('user_id', Auth::id())
                ->update([
                    'submitted_at' => now(),
                ]);
        }

        return redirect()->route('submissions.show', $submission)
            ->with('success', 'Your submission has been recorded. Tracking ID: '.$submission->tracking_id);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Submission $submission)
    {
        $user = $request->user();

        if ($user->isCitizen() && $submission->user_id !== $user->id) {
            abort(403);
        }

        $submission->load(['bill', 'user', 'reviewer']);

        if ($user->isLegislator()) {
            $house = $this->resolveLegislativeHouse($user);

            if ($submission->bill && ! in_array($submission->bill->house, [$house, 'both'], true)) {
                abort(403);
            }
        }

        return Inertia::render('Submissions/Show', [
            'submission' => $submission,
            'engagements' => $submission->engagements()->latest()->with('sender')->limit(10)->get()->map(fn ($engagement) => [
                'id' => $engagement->id,
                'subject' => $engagement->subject,
                'message' => $engagement->message,
                'sender' => [
                    'id' => $engagement->sender->id,
                    'name' => $engagement->sender->name,
                ],
                'sent_at' => $engagement->sent_at,
            ]),
            'canFollowUp' => $user->isLegislator() || $user->isAdmin(),
        ]);
    }

    /**
     * Update the specified resource in storage (for clerks/admins to review)
     */
    public function update(UpdateSubmissionStatusRequest $request, Submission $submission)
    {
        $this->authorize('update', $submission);

        $validated = $request->validated();

        $validated['reviewed_at'] = now();
        $validated['reviewed_by'] = Auth::id();

        $submission->update($validated);

        return redirect()->route('submissions.show', $submission)
            ->with('success', 'Submission status updated successfully.');
    }

    /**
     * Track submission by tracking ID
     */
    public function track(Request $request)
    {
        $request->validate([
            'tracking_id' => 'required|string|size:12',
        ]);

        $submission = Submission::where('tracking_id', $request->tracking_id)
            ->with(['bill'])
            ->first();

        if (! $submission) {
            return back()->with('error', 'Submission not found. Please check your tracking ID.');
        }

        return Inertia::render('Submissions/Track', [
            'submission' => $submission,
        ]);
    }

    /**
     * Show tracking form
     */
    public function trackForm()
    {
        return Inertia::render('Submissions/TrackForm');
    }

    private function resolveLegislativeHouse($user): string
    {
        if (! empty($user->legislative_house)) {
            return $user->legislative_house;
        }

        $role = $user->role instanceof UserRole ? $user->role : UserRole::from($user->role);

        return $role === UserRole::Senator ? 'senate' : 'national_assembly';
    }
}

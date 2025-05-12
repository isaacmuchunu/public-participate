<?php

namespace App\Http\Controllers;

use App\Http\Requests\Submission\StoreSubmissionDraftRequest;
use App\Http\Requests\Submission\UpdateSubmissionDraftRequest;
use App\Models\SubmissionDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubmissionDraftController extends Controller
{
    public function store(StoreSubmissionDraftRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $draft = SubmissionDraft::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'bill_id' => $validated['bill_id'],
            'submitted_at' => null,
        ]);

        $draft->submission_type = $validated['submission_type'] ?? $draft->submission_type;
        $draft->language = $validated['language'] ?? $draft->language;
        $draft->content = $validated['content'] ?? $draft->content;
        $draft->contact_information = $validated['contact_information'] ?? $draft->contact_information;
        $draft->attachments = $validated['attachments'] ?? $draft->attachments;
        $draft->save();

        return redirect()
            ->route('submissions.create', [
                'bill_id' => $draft->bill_id,
                'draft_id' => $draft->id,
            ])
            ->with('success', 'Draft saved.');
    }

    public function update(UpdateSubmissionDraftRequest $request, SubmissionDraft $submissionDraft): RedirectResponse
    {
        $this->authorizeDraft($request, $submissionDraft);

        $validated = $request->validated();

        if (array_key_exists('bill_id', $validated)) {
            $submissionDraft->bill_id = $validated['bill_id'];
        }

        if (array_key_exists('submission_type', $validated)) {
            $submissionDraft->submission_type = $validated['submission_type'];
        }

        if (array_key_exists('language', $validated)) {
            $submissionDraft->language = $validated['language'];
        }

        if (array_key_exists('content', $validated)) {
            $submissionDraft->content = $validated['content'];
        }

        if (array_key_exists('contact_information', $validated)) {
            $submissionDraft->contact_information = $validated['contact_information'];
        }

        if (array_key_exists('attachments', $validated)) {
            $submissionDraft->attachments = $validated['attachments'];
        }

        $submissionDraft->submitted_at = null;
        $submissionDraft->save();

        return redirect()
            ->route('submissions.create', [
                'bill_id' => $submissionDraft->bill_id,
                'draft_id' => $submissionDraft->id,
            ])
            ->with('success', 'Draft updated.');
    }

    public function destroy(Request $request, SubmissionDraft $submissionDraft): RedirectResponse
    {
        $this->authorizeDraft($request, $submissionDraft);

        $submissionDraft->delete();

        return redirect()
            ->route('submissions.create')
            ->with('success', 'Draft removed.');
    }

    private function authorizeDraft(Request $request, SubmissionDraft $submissionDraft): void
    {
        $user = $request->user();

        if ($submissionDraft->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403);
        }
    }
}

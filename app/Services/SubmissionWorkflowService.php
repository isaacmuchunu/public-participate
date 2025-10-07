<?php

namespace App\Services;

use App\Events\Submission\SubmissionCreated;
use App\Jobs\AI\AnalyzeSubmissionJob;
use App\Models\Bill;
use App\Models\Submission;
use App\Models\SubmissionDraft;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubmissionWorkflowService
{
    /**
     * Submit a draft as a formal submission
     */
    public function submitDraft(SubmissionDraft $draft): Submission
    {
        DB::beginTransaction();

        try {
            // Verify bill is open for participation
            $bill = Bill::findOrFail($draft->bill_id);

            if (! $bill->isOpenForParticipation()) {
                throw new \InvalidArgumentException('This bill is not currently open for public participation.');
            }

            // Create submission from draft
            $submission = Submission::create([
                'user_id' => $draft->user_id,
                'bill_id' => $draft->bill_id,
                'submission_type' => $draft->submission_type,
                'content' => $draft->content,
                'language' => $draft->language ?? 'en',
                'submitter_name' => $draft->submitter_name,
                'submitter_phone' => $draft->submitter_phone,
                'submitter_email' => $draft->submitter_email,
                'submitter_county' => $draft->submitter_county,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // Increment bill submission count
            $bill->increment('submissions_count');

            // Delete the draft
            $draft->delete();

            // Fire submission created event
            event(new SubmissionCreated($submission));

            // Queue AI analysis job
            AnalyzeSubmissionJob::dispatch($submission)->onQueue('analysis');

            DB::commit();

            Log::info("Submission {$submission->id} created from draft {$draft->id}");

            return $submission;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to submit draft {$draft->id}: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Review a submission (for clerks/admins)
     */
    public function reviewSubmission(
        Submission $submission,
        User $reviewer,
        string $status,
        ?string $notes = null
    ): Submission {
        DB::beginTransaction();

        try {
            // Validate status
            $validStatuses = ['approved', 'rejected', 'flagged', 'under_review'];

            if (! in_array($status, $validStatuses, true)) {
                throw new \InvalidArgumentException("Invalid status: {$status}");
            }

            // Validate reviewer has permission
            if (! $reviewer->isClerk() && ! $reviewer->isAdmin()) {
                throw new \InvalidArgumentException('Only clerks and admins can review submissions.');
            }

            // Update submission
            $submission->update([
                'status' => $status,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ]);

            // Send notification to submitter
            $submission->user->notify(
                new \App\Notifications\Submission\SubmissionReviewed($submission, $status, $notes)
            );

            DB::commit();

            Log::info("Submission {$submission->id} reviewed by user {$reviewer->id} with status {$status}");

            return $submission->fresh();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to review submission {$submission->id}: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Create a direct submission (without draft)
     */
    public function createSubmission(User $user, Bill $bill, array $data): Submission
    {
        DB::beginTransaction();

        try {
            // Verify bill is open for participation
            if (! $bill->isOpenForParticipation()) {
                throw new \InvalidArgumentException('This bill is not currently open for public participation.');
            }

            // Create submission
            $submission = Submission::create([
                'user_id' => $user->id,
                'bill_id' => $bill->id,
                'submission_type' => $data['submission_type'],
                'content' => $data['content'],
                'language' => $data['language'] ?? 'en',
                'submitter_name' => $data['submitter_name'] ?? null,
                'submitter_phone' => $data['submitter_phone'] ?? null,
                'submitter_email' => $data['submitter_email'] ?? null,
                'submitter_county' => $data['submitter_county'] ?? null,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // Increment bill submission count
            $bill->increment('submissions_count');

            // Fire submission created event
            event(new SubmissionCreated($submission));

            // Queue AI analysis job
            AnalyzeSubmissionJob::dispatch($submission)->onQueue('analysis');

            DB::commit();

            Log::info("Submission {$submission->id} created directly by user {$user->id}");

            return $submission;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to create submission: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Update submission status
     */
    public function updateStatus(Submission $submission, string $status): Submission
    {
        $validStatuses = ['submitted', 'under_review', 'approved', 'rejected', 'flagged'];

        if (! in_array($status, $validStatuses, true)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $submission->update(['status' => $status]);

        Log::info("Submission {$submission->id} status updated to {$status}");

        return $submission->fresh();
    }

    /**
     * Flag a submission for review
     */
    public function flagSubmission(Submission $submission, string $reason, ?User $flaggedBy = null): Submission
    {
        DB::beginTransaction();

        try {
            $submission->update([
                'status' => 'flagged',
                'flag_reason' => $reason,
                'flagged_by' => $flaggedBy?->id,
                'flagged_at' => now(),
            ]);

            // Notify admins
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(
                    new \App\Notifications\Submission\SubmissionFlagged($submission, $reason)
                );
            }

            DB::commit();

            Log::info("Submission {$submission->id} flagged: {$reason}");

            return $submission->fresh();
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to flag submission {$submission->id}: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Get submission statistics for a bill
     */
    public function getSubmissionStats(Bill $bill): array
    {
        $submissions = $bill->submissions();

        return [
            'total' => $submissions->count(),
            'by_type' => [
                'support' => $submissions->where('submission_type', 'support')->count(),
                'oppose' => $submissions->where('submission_type', 'oppose')->count(),
                'amend' => $submissions->where('submission_type', 'amend')->count(),
                'neutral' => $submissions->where('submission_type', 'neutral')->count(),
            ],
            'by_status' => [
                'submitted' => $submissions->where('status', 'submitted')->count(),
                'under_review' => $submissions->where('status', 'under_review')->count(),
                'approved' => $submissions->where('status', 'approved')->count(),
                'rejected' => $submissions->where('status', 'rejected')->count(),
                'flagged' => $submissions->where('status', 'flagged')->count(),
            ],
            'by_language' => [
                'en' => $submissions->where('language', 'en')->count(),
                'sw' => $submissions->where('language', 'sw')->count(),
                'other' => $submissions->where('language', 'other')->count(),
            ],
        ];
    }
}

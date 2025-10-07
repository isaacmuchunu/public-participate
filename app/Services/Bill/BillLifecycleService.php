<?php

namespace App\Services\Bill;

use App\Events\Bill\BillStatusChanged;
use App\Models\Bill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillLifecycleService
{
    /**
     * Valid status transitions
     */
    private const VALID_TRANSITIONS = [
        'draft' => ['published', 'withdrawn'],
        'published' => ['open_for_participation', 'withdrawn'],
        'open_for_participation' => ['closed', 'withdrawn'],
        'closed' => ['passed', 'rejected', 'under_review'],
        'under_review' => ['passed', 'rejected', 'amendments_required'],
        'amendments_required' => ['open_for_participation', 'withdrawn'],
        'passed' => ['enacted'],
        'rejected' => [],
        'enacted' => [],
        'withdrawn' => [],
    ];

    /**
     * Statuses that require specific date validations
     */
    private const DATE_REQUIRED_STATUSES = [
        'open_for_participation' => ['participation_start_date', 'participation_end_date'],
        'published' => ['gazette_date'],
    ];

    /**
     * Transition a bill to a new status
     */
    public function transitionTo(Bill $bill, string $newStatus, ?array $additionalData = null): bool
    {
        DB::beginTransaction();

        try {
            // Validate transition
            if (! $this->isValidTransition($bill->status, $newStatus)) {
                throw new \InvalidArgumentException(
                    "Cannot transition from '{$bill->status}' to '{$newStatus}'"
                );
            }

            // Validate required dates
            $this->validateRequiredDates($bill, $newStatus);

            // Store old status for event
            $oldStatus = $bill->status;

            // Update bill status
            $updateData = ['status' => $newStatus];

            // Merge any additional data (e.g., dates)
            if ($additionalData) {
                $updateData = array_merge($updateData, $additionalData);
            }

            $bill->update($updateData);

            // Fire status changed event
            event(new BillStatusChanged($bill, $oldStatus, $newStatus));

            DB::commit();

            Log::info("Bill {$bill->id} transitioned from {$oldStatus} to {$newStatus}");

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to transition bill {$bill->id}: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Check if a status transition is valid
     */
    public function isValidTransition(string $currentStatus, string $newStatus): bool
    {
        // Same status is not a transition
        if ($currentStatus === $newStatus) {
            return false;
        }

        // Check if transition is allowed
        return in_array($newStatus, self::VALID_TRANSITIONS[$currentStatus] ?? [], true);
    }

    /**
     * Get valid next statuses for a bill
     */
    public function getValidNextStatuses(Bill $bill): array
    {
        return self::VALID_TRANSITIONS[$bill->status] ?? [];
    }

    /**
     * Validate required dates for status transition
     */
    protected function validateRequiredDates(Bill $bill, string $newStatus): void
    {
        if (! isset(self::DATE_REQUIRED_STATUSES[$newStatus])) {
            return;
        }

        $requiredDates = self::DATE_REQUIRED_STATUSES[$newStatus];

        foreach ($requiredDates as $dateField) {
            if (! $bill->$dateField) {
                throw new \InvalidArgumentException(
                    "Cannot transition to '{$newStatus}': '{$dateField}' is required"
                );
            }
        }

        // Additional validation for participation dates
        if ($newStatus === 'open_for_participation') {
            if ($bill->participation_start_date > $bill->participation_end_date) {
                throw new \InvalidArgumentException(
                    'Participation start date must be before end date'
                );
            }

            if ($bill->participation_end_date < now()) {
                throw new \InvalidArgumentException(
                    'Participation end date must be in the future'
                );
            }
        }
    }

    /**
     * Close bills that have passed their participation end date
     */
    public function closeExpiredBills(): int
    {
        $expiredBills = Bill::query()
            ->where('status', 'open_for_participation')
            ->where('participation_end_date', '<', now())
            ->get();

        $closedCount = 0;

        foreach ($expiredBills as $bill) {
            try {
                $this->transitionTo($bill, 'closed');
                $closedCount++;
            } catch (\Exception $e) {
                Log::error("Failed to close expired bill {$bill->id}: {$e->getMessage()}");
            }
        }

        if ($closedCount > 0) {
            Log::info("Closed {$closedCount} expired bills");
        }

        return $closedCount;
    }

    /**
     * Open bills for participation if their start date has arrived
     */
    public function openScheduledBills(): int
    {
        $scheduledBills = Bill::query()
            ->where('status', 'published')
            ->whereNotNull('participation_start_date')
            ->whereNotNull('participation_end_date')
            ->where('participation_start_date', '<=', now())
            ->where('participation_end_date', '>=', now())
            ->get();

        $openedCount = 0;

        foreach ($scheduledBills as $bill) {
            try {
                $this->transitionTo($bill, 'open_for_participation');
                $openedCount++;
            } catch (\Exception $e) {
                Log::error("Failed to open scheduled bill {$bill->id}: {$e->getMessage()}");
            }
        }

        if ($openedCount > 0) {
            Log::info("Opened {$openedCount} scheduled bills");
        }

        return $openedCount;
    }

    /**
     * Check if a bill can be edited
     */
    public function canEdit(Bill $bill): bool
    {
        // Bills can only be edited in draft or amendments_required status
        return in_array($bill->status, ['draft', 'amendments_required'], true);
    }

    /**
     * Check if a bill can be deleted
     */
    public function canDelete(Bill $bill): bool
    {
        // Bills can only be deleted if in draft status and have no submissions
        return $bill->status === 'draft' && $bill->submissions_count === 0;
    }

    /**
     * Get all possible bill statuses
     */
    public static function getAllStatuses(): array
    {
        return array_keys(self::VALID_TRANSITIONS);
    }

    /**
     * Get human-readable status label
     */
    public static function getStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'published' => 'Published',
            'open_for_participation' => 'Open for Participation',
            'closed' => 'Closed',
            'under_review' => 'Under Review',
            'amendments_required' => 'Amendments Required',
            'passed' => 'Passed',
            'rejected' => 'Rejected',
            'enacted' => 'Enacted',
            'withdrawn' => 'Withdrawn',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}

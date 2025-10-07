<?php

namespace App\Services;

use App\Events\Engagement\MessageSent;
use App\Models\Bill;
use App\Models\CitizenEngagement;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EngagementService
{
    /**
     * Send a message from citizen to legislator
     */
    public function sendMessage(
        User $citizen,
        User $legislator,
        Bill $bill,
        ?Submission $submission,
        string $subject,
        string $message
    ): CitizenEngagement {
        DB::beginTransaction();

        try {
            // Validate sender is a citizen
            if (! $citizen->isCitizen()) {
                throw new \InvalidArgumentException('Only citizens can send engagement messages.');
            }

            // Validate recipient is a legislator
            if (! $legislator->isLegislator()) {
                throw new \InvalidArgumentException('Messages can only be sent to legislators.');
            }

            // Validate citizen can contact this legislator
            if (! $this->canContact($citizen, $legislator)) {
                throw new \InvalidArgumentException(
                    'You can only contact legislators from your constituency or county.'
                );
            }

            // Create engagement record
            $engagement = CitizenEngagement::create([
                'bill_id' => $bill->id,
                'submission_id' => $submission?->id,
                'sender_id' => $citizen->id,
                'recipient_id' => $legislator->id,
                'subject' => $subject,
                'message' => $message,
                'channel' => 'platform',
                'sent_at' => now(),
            ]);

            // Fire message sent event
            event(new MessageSent($engagement));

            // Queue notification to legislator
            $legislator->notify(
                new \App\Notifications\Engagement\NewEngagementMessage($engagement)
            );

            DB::commit();

            Log::info("Engagement message sent from user {$citizen->id} to user {$legislator->id}");

            return $engagement;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to send engagement message: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Check if a citizen can contact a legislator
     */
    public function canContact(User $citizen, User $legislator): bool
    {
        // Citizens can contact legislators from their constituency or county

        // For MPs: check constituency match
        if ($legislator->role->value === 'mp' && $legislator->constituency_id) {
            return $citizen->constituency_id === $legislator->constituency_id;
        }

        // For Senators: check county match
        if ($legislator->role->value === 'senator' && $legislator->county_id) {
            return $citizen->county_id === $legislator->county_id;
        }

        // Default: cannot contact if no matching jurisdiction
        return false;
    }

    /**
     * Get engagements for a user
     */
    public function getUserEngagements(User $user, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = CitizenEngagement::query();

        // Filter by role
        if ($user->isCitizen()) {
            $query->where('sender_id', $user->id);
        } elseif ($user->isLegislator()) {
            $query->where('recipient_id', $user->id);
        } else {
            // Clerks/admins can see all
            // No additional filter needed
        }

        // Apply additional filters
        if (isset($filters['bill_id'])) {
            $query->where('bill_id', $filters['bill_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Eager load relationships
        $query->with(['bill', 'submission', 'sender', 'recipient']);

        // Order by most recent first
        $query->latest('sent_at');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Mark engagement as read
     */
    public function markAsRead(CitizenEngagement $engagement, User $user): CitizenEngagement
    {
        // Only recipient can mark as read
        if ($engagement->recipient_id !== $user->id) {
            throw new \InvalidArgumentException('Only the recipient can mark this message as read.');
        }

        $engagement->update([
            'read_at' => now(),
        ]);

        Log::info("Engagement {$engagement->id} marked as read by user {$user->id}");

        return $engagement->fresh();
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(User $user): int
    {
        if (! $user->isLegislator()) {
            return 0;
        }

        return CitizenEngagement::query()
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Get engagement statistics
     */
    public function getEngagementStats(Bill $bill): array
    {
        $engagements = $bill->citizenEngagements();

        return [
            'total' => $engagements->count(),
            'by_channel' => [
                'platform' => $engagements->where('channel', 'platform')->count(),
                'email' => $engagements->where('channel', 'email')->count(),
                'sms' => $engagements->where('channel', 'sms')->count(),
            ],
            'response_rate' => $this->calculateResponseRate($bill),
        ];
    }

    /**
     * Calculate response rate for engagements
     */
    protected function calculateResponseRate(Bill $bill): float
    {
        $total = $bill->citizenEngagements()->count();

        if ($total === 0) {
            return 0.0;
        }

        $responded = $bill->citizenEngagements()
            ->whereNotNull('response_at')
            ->count();

        return round(($responded / $total) * 100, 2);
    }

    /**
     * Get legislators a citizen can contact for a bill
     */
    public function getAvailableLegislators(User $citizen, Bill $bill): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::query()
            ->whereIn('role', ['mp', 'senator'])
            ->where('is_verified', true)
            ->whereNull('suspended_at');

        // Filter by citizen's location
        if ($citizen->constituency_id) {
            $query->where(function ($q) use ($citizen) {
                // MPs from same constituency
                $q->where(function ($subQ) use ($citizen) {
                    $subQ->where('role', 'mp')
                        ->where('constituency_id', $citizen->constituency_id);
                });

                // Senators from same county
                if ($citizen->county_id) {
                    $q->orWhere(function ($subQ) use ($citizen) {
                        $subQ->where('role', 'senator')
                            ->where('county_id', $citizen->county_id);
                    });
                }
            });
        }

        return $query->get();
    }
}

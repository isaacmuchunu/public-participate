<?php

namespace App\Services;

use App\Models\User;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class SmsNotificationService
{
    private Client $twilio;

    public function __construct()
    {
        $this->twilio = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    /**
     * Send bill publication notification via SMS
     */
    public function sendBillPublishedNotification(User $user, $bill): bool
    {
        if (!$this->canSendSms($user)) {
            return false;
        }

        $message = $this->formatBillPublishedMessage($bill);

        return $this->sendSms($user->phone, $message);
    }

    /**
     * Send participation reminder via SMS
     */
    public function sendParticipationReminder(User $user, $bill): bool
    {
        if (!$this->canSendSms($user)) {
            return false;
        }

        $message = $this->formatParticipationReminderMessage($bill);

        return $this->sendSms($user->phone, $message);
    }

    /**
     * Send submission confirmation via SMS
     */
    public function sendSubmissionConfirmation(User $user, $submission): bool
    {
        if (!$this->canSendSms($user)) {
            return false;
        }

        $message = $this->formatSubmissionConfirmationMessage($submission);

        return $this->sendSms($user->phone, $message);
    }

    /**
     * Send participation deadline reminder
     */
    public function sendDeadlineReminder(User $user, $bill): bool
    {
        if (!$this->canSendSms($user)) {
            return false;
        }

        $message = $this->formatDeadlineReminderMessage($bill);

        return $this->sendSms($user->phone, $message);
    }

    /**
     * Check if user can receive SMS notifications
     */
    private function canSendSms(User $user): bool
    {
        // Check if user has phone number
        if (!$user->phone) {
            return false;
        }

        // Check if user has opted in for SMS notifications
        if (!$user->sms_notifications_enabled) {
            return false;
        }

        // Check if phone number is verified (if required)
        if (config('services.twilio.require_verification') && !$user->phone_verified_at) {
            return false;
        }

        return true;
    }

    /**
     * Send SMS using Twilio
     */
    private function sendSms(string $phone, string $message): bool
    {
        try {
            $this->twilio->messages->create(
                $phone,
                [
                    'from' => config('services.twilio.from'),
                    'body' => $message,
                ]
            );

            Log::info('SMS sent successfully', [
                'phone' => $phone,
                'message_length' => strlen($message)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SMS sending failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Format bill published message
     */
    private function formatBillPublishedMessage($bill): string
    {
        return "New Bill Published: {$bill->title} ({$bill->bill_number}). " .
               "Open for public participation until " . $bill->participation_end_date->format('M j, Y') . ". " .
               "View and comment: " . route('bills.show', $bill->id);
    }

    /**
     * Format participation reminder message
     */
    private function formatParticipationReminderMessage($bill): string
    {
        $daysLeft = now()->diffInDays($bill->participation_end_date, false);

        return "Reminder: {$bill->title} closes in {$daysLeft} days. " .
               "Share your thoughts before it's too late! " .
               route('bills.show', $bill->id);
    }

    /**
     * Format submission confirmation message
     */
    private function formatSubmissionConfirmationMessage($submission): string
    {
        return "Thank you! Your submission on {$submission->bill->title} has been received. " .
               "Tracking ID: {$submission->tracking_id}. " .
               "We'll review and get back to you soon.";
    }

    /**
     * Format deadline reminder message
     */
    private function formatDeadlineReminderMessage($bill): string
    {
        return "Final Reminder: {$bill->title} participation closes TODAY. " .
               "Don't miss your chance to contribute! " .
               route('bills.show', $bill->id);
    }

    /**
     * Bulk send notifications (for admin use)
     */
    public function sendBulkNotification(array $userIds, string $message): array
    {
        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user && $this->canSendSms($user)) {
                if ($this->sendSms($user->phone, $message)) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to send to user {$userId}";
                }
            } else {
                $results['failed']++;
                $results['errors'][] = "User {$userId} cannot receive SMS";
            }
        }

        return $results;
    }
}

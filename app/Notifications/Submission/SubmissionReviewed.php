<?php

namespace App\Notifications\Submission;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Submission $submission,
        public string $status,
        public ?string $notes = null
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = match ($this->status) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'flagged' => 'Flagged for Review',
            'under_review' => 'Under Review',
            default => ucfirst($this->status),
        };

        $message = (new MailMessage)
            ->subject("Your Submission has been {$statusLabel}")
            ->line("Your submission on '{$this->submission->bill->title}' has been {$statusLabel}.");

        if ($this->notes) {
            $message->line("Reviewer Notes: {$this->notes}");
        }

        $message->action('View Submission', url('/submissions/'.$this->submission->id))
            ->line('Thank you for participating!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'bill_id' => $this->submission->bill_id,
            'bill_title' => $this->submission->bill->title,
            'status' => $this->status,
            'notes' => $this->notes,
            'reviewed_at' => $this->submission->reviewed_at,
        ];
    }
}

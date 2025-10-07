<?php

namespace App\Notifications\Submission;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubmissionFlagged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Submission $submission,
        public string $reason
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
        return (new MailMessage)
            ->subject('Submission Flagged for Review')
            ->line("A submission on '{$this->submission->bill->title}' has been flagged.")
            ->line("Reason: {$this->reason}")
            ->line("Submitter: {$this->submission->user->name}")
            ->action('Review Submission', url('/admin/submissions/'.$this->submission->id))
            ->line('Please review this submission as soon as possible.');
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
            'submitter_id' => $this->submission->user_id,
            'submitter_name' => $this->submission->user->name,
            'reason' => $this->reason,
            'flagged_at' => $this->submission->flagged_at,
        ];
    }
}

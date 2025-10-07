<?php

namespace App\Notifications\Engagement;

use App\Models\CitizenEngagement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEngagementMessage extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public CitizenEngagement $engagement
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
            ->subject('New Message from Constituent')
            ->line("You have received a new message from {$this->engagement->sender->name}.")
            ->line("Regarding: {$this->engagement->bill->title}")
            ->line("Subject: {$this->engagement->subject}")
            ->action('View Message', url('/engagements/'.$this->engagement->id))
            ->line('Thank you for your service!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'engagement_id' => $this->engagement->id,
            'sender_id' => $this->engagement->sender_id,
            'sender_name' => $this->engagement->sender->name,
            'bill_id' => $this->engagement->bill_id,
            'bill_title' => $this->engagement->bill->title,
            'subject' => $this->engagement->subject,
            'message' => $this->engagement->message,
            'sent_at' => $this->engagement->sent_at,
        ];
    }
}

<?php

namespace App\Notifications\Engagement;

use App\Models\CitizenEngagement;
use App\Notifications\Channels\TwilioSmsChannel;
use App\Notifications\Messages\TwilioMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class LegislatorFollowUpNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly CitizenEngagement $engagement)
    {
        $this->queue = 'notifications';
    }

    public function via(object $notifiable): array
    {
        return ['mail', TwilioSmsChannel::class, 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A legislator would like to follow up with you')
            ->greeting('Hello '.$notifiable->name)
            ->line($this->engagement->sender->name.' has reached out regarding your feedback on '.$this->engagement->bill->title.'.')
            ->line('Message: '.$this->engagement->message)
            ->action('Reply to the follow-up', route('notifications.index'));
    }

    public function toTwilio(object $notifiable): TwilioMessage
    {
        return TwilioMessage::make(
            $this->engagement->sender->name.' requested more info about your feedback on '.$this->engagement->bill->title.'. Check your portal messages for details.'
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'engagement_id' => $this->engagement->id,
            'bill_id' => $this->engagement->bill_id,
            'bill_title' => $this->engagement->bill->title,
            'submission_id' => $this->engagement->submission_id,
            'sender' => $this->engagement->sender->only(['id', 'name', 'email']),
            'subject' => $this->engagement->subject,
            'message' => $this->engagement->message,
            'type' => 'legislator_follow_up',
        ];
    }
}

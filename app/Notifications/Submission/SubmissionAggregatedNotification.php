<?php

namespace App\Notifications\Submission;

use App\Models\Submission;
use App\Notifications\Channels\TwilioSmsChannel;
use App\Notifications\Messages\TwilioMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class SubmissionAggregatedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly Submission $submission)
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
            ->subject('Your submission has been aggregated')
            ->greeting('Hello '.$notifiable->name)
            ->line('Thanks for sharing your views on '.$this->submission->bill?->title.'.')
            ->line('Your submission (Tracking ID '.$this->submission->tracking_id.') has now been aggregated into the committee report.')
            ->line('We will keep you posted on the committee deliberations.')
            ->action('View submission status', route('submissions.show', $this->submission));
    }

    public function toTwilio(object $notifiable): TwilioMessage
    {
        $billTitle = $this->submission->bill?->title ?? 'your bill feedback';

        return TwilioMessage::make('Update: Your submission for '.$billTitle.' (ID '.$this->submission->tracking_id.') has been aggregated. Thank you for participating.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'submission_id' => $this->submission->id,
            'tracking_id' => $this->submission->tracking_id,
            'bill_id' => $this->submission->bill_id,
            'bill_title' => $this->submission->bill?->title,
            'type' => 'submission_aggregated',
        ];
    }
}

<?php

namespace App\Notifications\Bill;

use App\Models\Bill;
use App\Notifications\Channels\TwilioSmsChannel;
use App\Notifications\Messages\TwilioMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class BillParticipationOpenedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly Bill $bill)
    {
        $this->queue = 'notifications';
    }

    public function via(object $notifiable): array
    {
        return ['mail', TwilioSmsChannel::class, 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Commentary Open: '.$this->bill->title)
            ->greeting('Hello '.$notifiable->name)
            ->line('The commentary period for a bill you follow is now open.')
            ->line('Bill: '.$this->bill->title.' ('.$this->bill->bill_number.')')
            ->action('Share your feedback', route('bills.show', $this->bill));

        if ($this->bill->participation_end_date) {
            $message->line('Submissions close on '.$this->bill->participation_end_date->format('j M Y').'.');
        }

        return $message;
    }

    public function toTwilio(object $notifiable): TwilioMessage
    {
        $deadline = $this->bill->participation_end_date?->format('j M');

        return TwilioMessage::make(
            trim(implode(' ', [
                'COMMENTARY OPEN:',
                $this->bill->title,
                $deadline ? "(closes {$deadline})" : '',
                route('bills.show', $this->bill),
            ]))
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'bill_id' => $this->bill->id,
            'title' => $this->bill->title,
            'bill_number' => $this->bill->bill_number,
            'participation_end_date' => optional($this->bill->participation_end_date)->toDateString(),
            'type' => 'participation_opened',
        ];
    }
}

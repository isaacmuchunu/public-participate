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

class NewBillPublishedNotification extends Notification implements ShouldQueue
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
            ->subject('New Bill Published: '.$this->bill->title)
            ->greeting('Hello '.$notifiable->name)
            ->line('A new bill has been published for public participation.')
            ->line('Bill: '.$this->bill->title.' ('.$this->bill->bill_number.')')
            ->action('View bill details', route('bills.show', $this->bill));

        if ($this->bill->participation_start_date) {
            $message->line('Commentary opens on '.$this->bill->participation_start_date->format('j M Y').'.');
        }

        return $message;
    }

    public function toTwilio(object $notifiable): TwilioMessage
    {
        $date = $this->bill->participation_start_date?->format('j M');

        return TwilioMessage::make(
            trim(implode(' ', [
                'NEW BILL:',
                $this->bill->title,
                $date ? "(opens {$date})" : '',
                'Read more:',
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
            'participation_start_date' => optional($this->bill->participation_start_date)->toDateString(),
            'type' => 'bill_published',
        ];
    }
}

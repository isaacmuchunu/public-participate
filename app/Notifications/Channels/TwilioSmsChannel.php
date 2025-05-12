<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\TwilioMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TwilioSmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notifiable, 'routeNotificationForTwilio')) {
            return;
        }

        $phoneNumber = $notifiable->routeNotificationForTwilio($notification);

        if (empty($phoneNumber)) {
            return;
        }

        $message = $notification->toTwilio($notifiable);

        if (! $message instanceof TwilioMessage) {
            $message = TwilioMessage::make((string) $message);
        }

        $payload = $message->toArray();

        if (! isset($payload['Body']) || $payload['Body'] === '') {
            return;
        }

        $payload['To'] = $phoneNumber;
        $payload['From'] = config('services.twilio.from');

        if (empty($payload['From'])) {
            throw new RuntimeException('Twilio from number is not configured.');
        }

        $accountSid = config('services.twilio.sid');
        $authToken = config('services.twilio.token');

        if (empty($accountSid) || empty($authToken)) {
            throw new RuntimeException('Twilio credentials are not configured.');
        }

        Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", $payload)
            ->throw();
    }
}

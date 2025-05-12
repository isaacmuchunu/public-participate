<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RegistrationOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $otp) {}

    public function otp(): string
    {
        return $this->otp;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiresInMinutes = config('auth.otp.expires', 10);

        return (new MailMessage)
            ->subject('Verify your citizen account')
            ->greeting('Hello '.$notifiable->name)
            ->line('Use the one-time passcode below to verify your account with the Public Participation System:')
            ->line('OTP: '.$this->otp)
            ->line('This code will expire in '.$expiresInMinutes.' minute'.($expiresInMinutes === 1 ? '' : 's').'.')
            ->line('If you did not request this code, please ignore this email.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Registration verification code sent.',
        ];
    }
}

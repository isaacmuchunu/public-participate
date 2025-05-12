<?php

namespace App\Notifications\Legislator;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly User $inviter,
        private readonly string $token,
        private readonly Carbon $expiresAt,
        private readonly ?string $customMessage = null,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $mail = (new MailMessage)
            ->subject('Invitation to the Parliamentary Participation Platform')
            ->greeting('Hello '.$notifiable->name)
            ->line($this->inviter->name.' has invited you to access the Public Participation workspace as a legislator.')
            ->line('Use the link below to activate your account. This invitation expires on '.$this->expiresAt->toFormattedDateString().'.')
            ->action('Accept invitation', $this->invitationUrl())
            ->line('If you did not expect this invitation, please contact '.$this->inviter->email.'.');

        if ($this->customMessage) {
            $mail->line('Message from '.$this->inviter->name.': '.$this->customMessage);
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'inviter' => [
                'id' => $this->inviter->id,
                'name' => $this->inviter->name,
                'email' => $this->inviter->email,
            ],
            'expires_at' => $this->expiresAt,
            'token' => $this->token,
        ];
    }

    private function invitationUrl(): string
    {
        return url('/invitations/accept/'.$this->token);
    }
}

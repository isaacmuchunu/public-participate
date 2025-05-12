<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\Legislator\InvitationNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SendLegislatorInvitation implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly User $legislator,
        private readonly User $inviter,
        private readonly ?string $customMessage = null,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        $this->legislator->refresh();
        $this->legislator->loadMissing('inviter');

        if (! $this->legislator->email) {
            return;
        }

        $expiresAt = $this->legislator->invitation_expires_at instanceof Carbon
            ? $this->legislator->invitation_expires_at
            : Carbon::now()->addDays(7);

        $token = $this->legislator->invitation_token;

        if (! $token) {
            return;
        }

        $this->legislator->notify(new InvitationNotification(
            inviter: $this->inviter,
            token: $token,
            expiresAt: $expiresAt,
            customMessage: $this->customMessage,
        ));
    }
}

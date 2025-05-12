<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegislatorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->role;

        $invitationStatus = $this->email_verified_at
            ? 'active'
            : ($this->invitation_expires_at && $this->invitation_expires_at->isPast()
                ? 'expired'
                : 'pending');

        if ($this->isSuspended()) {
            $invitationStatus = 'suspended';
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $role instanceof \BackedEnum ? $role->value : $role,
            'legislative_house' => $this->legislative_house,
            'county' => $this->county,
            'constituency' => $this->constituency,
            'status' => [
                'is_suspended' => $this->isSuspended(),
                'suspended_at' => $this->suspended_at,
                'is_verified' => (bool) $this->is_verified,
            ],
            'invitation' => [
                'sent_at' => $this->invited_at,
                'expires_at' => $this->invitation_expires_at,
                'status' => $invitationStatus,
                'token' => $request->user()?->isClerk() || $request->user()?->isAdmin() ? $this->invitation_token : null,
            ],
            'invited_by' => $this->whenLoaded('inviter', fn () => [
                'id' => $this->inviter->id,
                'name' => $this->inviter->name,
                'email' => $this->inviter->email,
            ]),
            'last_active_at' => $this->last_active_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitizenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->role;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $role instanceof \BackedEnum ? $role->value : $role,
            'county' => $this->county,
            'constituency' => $this->constituency,
            'is_verified' => (bool) $this->is_verified,
            'suspended_at' => $this->suspended_at,
            'created_at' => $this->created_at,
            'last_active_at' => $this->last_active_at,
            'submissions_count' => $this->when(isset($this->submissions_count), $this->submissions_count),
            'last_submission_at' => $this->when(isset($this->last_submission_at), $this->last_submission_at),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_id' => $this->tracking_id,
            'bill_id' => $this->bill_id,
            'user_id' => $this->user_id,
            'submitter_name' => $this->submitter_name,
            'submitter_phone' => $this->submitter_phone,
            'submitter_email' => $this->submitter_email,
            'submitter_county' => $this->submitter_county,
            'submission_type' => $this->submission_type,
            'content' => $this->content,
            'channel' => $this->channel,
            'language' => $this->language,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'review_notes' => $this->review_notes,
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'reviewed_by' => $this->reviewed_by,
            'bill' => BillResource::make($this->whenLoaded('bill')),
            'reviewer' => $this->whenLoaded('reviewer', function () {
                return [
                    'id' => $this->reviewer->id,
                    'name' => $this->reviewer->name,
                    'email' => $this->reviewer->email,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

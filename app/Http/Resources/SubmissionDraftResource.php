<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionDraftResource extends JsonResource
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
            'bill_id' => $this->bill_id,
            'submission_type' => $this->submission_type,
            'language' => $this->language,
            'content' => $this->content,
            'contact_information' => $this->contact_information ?? [],
            'attachments' => $this->attachments ?? [],
            'submitted_at' => $this->submitted_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'bill' => $this->whenLoaded('bill', function () {
                return [
                    'id' => $this->bill->id,
                    'title' => $this->bill->title,
                    'bill_number' => $this->bill->bill_number,
                    'status' => $this->bill->status,
                    'participation_end_date' => $this->bill->participation_end_date?->toDateString(),
                ];
            }),
        ];
    }
}

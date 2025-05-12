<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegislatorHighlightResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'clause_reference' => $this->clause_reference,
            'excerpt' => $this->excerpt,
            'note' => $this->note,
            'highlighted_at' => $this->highlighted_at,
            'created_at' => $this->created_at,
            'metadata' => $this->metadata ?? [],
            'bill' => BillResource::make($this->whenLoaded('bill')),
            'submission' => $this->whenLoaded('submission', function ($submission) {
                return [
                    'id' => $submission->id,
                    'tracking_id' => $submission->tracking_id,
                    'submission_type' => $submission->submission_type,
                    'submitter_name' => $submission->submitter_name,
                    'content' => $submission->content,
                ];
            }),
        ];
    }
}

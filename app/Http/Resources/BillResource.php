<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillResource extends JsonResource
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
            'title' => $this->title,
            'bill_number' => $this->bill_number,
            'description' => $this->description,
            'type' => $this->type,
            'house' => $this->house,
            'status' => $this->status,
            'sponsor' => $this->sponsor,
            'committee' => $this->committee,
            'gazette_date' => $this->gazette_date?->toDateString(),
            'participation_start_date' => $this->participation_start_date?->toDateString(),
            'participation_end_date' => $this->participation_end_date?->toDateString(),
            'tags' => $this->tags ?? [],
            'views_count' => $this->views_count,
            'submissions_count' => $this->submissions_count,
            'is_open_for_participation' => $this->isOpenForParticipation(),
            'summary' => $this->whenLoaded('summary', function () {
                return [
                    'simplified_summary_en' => $this->summary->simplified_summary_en,
                    'simplified_summary_sw' => $this->summary->simplified_summary_sw,
                    'key_clauses' => $this->summary->key_clauses,
                    'generation_method' => $this->summary->generation_method,
                    'generated_at' => $this->summary->generated_at?->toDateTimeString(),
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

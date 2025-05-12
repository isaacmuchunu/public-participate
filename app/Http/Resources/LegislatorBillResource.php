<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegislatorBillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $submissionStats = [
            'total' => $this->submissions_count ?? 0,
            'pending' => $this->pending_submissions_count ?? null,
            'reviewed' => $this->reviewed_submissions_count ?? null,
            'aggregated' => $this->aggregated_submissions_count ?? null,
        ];

        return [
            'id' => $this->id,
            'title' => $this->title,
            'bill_number' => $this->bill_number,
            'description' => $this->when(isset($this->description), $this->description),
            'committee' => $this->when(isset($this->committee), $this->committee),
            'sponsor' => $this->when(isset($this->sponsor), $this->sponsor),
            'status' => $this->status,
            'house' => $this->house,
            'type' => $this->type,
            'participation_end_date' => $this->participation_end_date,
            'participation_start_date' => $this->participation_start_date,
            'is_open_for_participation' => method_exists($this->resource, 'isOpenForParticipation')
                ? $this->resource->isOpenForParticipation()
                : null,
            'tags' => $this->when(isset($this->tags), $this->tags),
            'submission_stats' => array_filter($submissionStats, static fn ($value) => $value !== null),
            'highlights_count' => $this->when(isset($this->highlights_count), $this->highlights_count),
            'summary' => $this->whenLoaded('summary', function () {
                return [
                    'simplified_summary_en' => $this->summary->simplified_summary_en,
                    'simplified_summary_sw' => $this->summary->simplified_summary_sw,
                    'key_clauses' => $this->summary->key_clauses,
                    'generation_method' => $this->summary->generation_method,
                    'generated_at' => $this->summary->generated_at,
                ];
            }),
            'updated_at' => $this->updated_at,
        ];
    }
}

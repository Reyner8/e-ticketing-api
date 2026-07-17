<?php

namespace App\Http\Resources\Milestone;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MilestoneResource extends JsonResource
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
            'feature_request_id' => $this->feature_request_id,
            'title' => $this->title,
            'description' => $this->description,
            'target_date' => $this->target_date?->format('Y-m-d H:i:s'),
            'completed_date' => $this->completed_date?->format('Y-m-d H:i:s'),
            'is_completed' => $this->is_completed,
            'progress' => $this->progress,
            'is_overdue' => $this->isOverdue(),
            'days_remaining' => $this->isCompleted() ? null : $this->days_remaining,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

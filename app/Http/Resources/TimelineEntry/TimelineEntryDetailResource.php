<?php

namespace App\Http\Resources\TimelineEntry;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelineEntryDetailResource extends JsonResource
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
            'phase' => [
                'value' => $this->phase->value,
                'label' => $this->phase->label(),
                'order' => $this->phase->order()
            ],
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $this->start_date?->timezone('Asia/Makassar'),
            'end_date' => $this->end_date?->format('Y-m-d H:i:s'),
            'duration' => $this->duration_days,
            'progress' => $this->progress,
            'is_completed' => $this->is_completed,
            'is_overdue' => $this->isOverdue(),
            'assigned_to' => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'username' => $this->assignee->username,
            ]: null,
        ];
    }
}

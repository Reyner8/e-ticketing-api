<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DowntimeRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $mapComponent = fn ($component) => [
            'id' => $component->id,
            'code' => $component->code,
            'name' => $component->name,
            'category' => [
                'value' => $component->category?->value ?? $component->category,
                'label' => $component->category?->label() ?? (string) $component->category,
            ],
            'is_active' => (bool) $component->is_active,
        ];

        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
            ],
            'reason' => $this->reason,
            'start_time' => $this->start_time?->format('Y-m-d H:i:s'),
            'end_time' => $this->end_time?->format('Y-m-d H:i:s'),
            'duration' => [
                'minutes' => $this->duration,
                'formatted' => $this->formatted_duration,
            ],
            'impact' => [
                'value' => $this->impact->value,
                'label' => $this->impact->label(),
            ],
            'reported_by' => $this->whenLoaded('reporter', fn () => $this->reporter ? [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
                'username' => $this->reporter->username,
            ] : null),
            'location' => $this->whenLoaded('location', fn () => $this->location ? [
                'id' => $this->location->id,
                'code' => $this->location->code,
                'name' => $this->location->name,
                'is_active' => (bool) $this->location->is_active,
            ] : null),
            'source_components' => $this->whenLoaded('sourceComponents', fn () => $this->sourceComponents->map($mapComponent)->values()),
            'affected_components' => $this->whenLoaded('affectedComponents', fn () => $this->affectedComponents->map($mapComponent)->values()),
            'description' => $this->description,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'root_cause' => $this->root_cause,
            'preventive_measures' => $this->preventive_measures,
            'affected_users' => $this->affected_users,
            'estimated_cost' => $this->estimated_cost,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

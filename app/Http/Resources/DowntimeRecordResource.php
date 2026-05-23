<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DowntimeRecordResource extends JsonResource
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
            'type' => [
                'value' => $this->type->value,
                'label' =>$this->type->label()
            ],
            'reason' => $this->reason,
            'start_time' => $this->start_time?->format('Y-m-d H:i:s'),
            'end_time' => $this->end_time?->format('Y-m-d H:i:s'),
            'duration' => [
                'minutes' => $this->duration,
                'formatted' => $this->formatted_duration
            ],
            'impact' => [
                'value' => $this->impact->value,
                'label' => $this->impact->label()
            ],
            'reported_by' => [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
                'username' => $this->reporter->username,
            ],
            'description' => $this->description,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label()
            ],
            'root_cause' => $this->root_cause,
            'preventive_measures' => $this->preventive_measures,
            'affected_users'=> $this->affected_users,
            'estimated_cost' => $this->estimated_cost,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

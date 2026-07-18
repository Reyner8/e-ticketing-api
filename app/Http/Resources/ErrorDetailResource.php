<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorDetailResource extends JsonResource
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
            'description' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
            'reporter_id' => $this->reporter_id,
            'assigned_to_id' => $this->assigned_to_id,
            'assigned_team' => $this->assigned_team,
            'date_reported' => $this->date_reported->format('Y-m-d H:i:s'),
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'completion_date' => $this->completion_date,
            'sla_breached' => $this->sla_breached,
            'source_ticket_id' => $this->source_ticket_id,
            'is_direct_input' => $this->is_direct_input,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

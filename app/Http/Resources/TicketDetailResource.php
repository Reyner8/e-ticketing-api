<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketDetailResource extends JsonResource
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
            'date_reported' => $this->date_reported->format('Y-m-d H:i:s'),
            'due_date' => $this->due_date,
            'resolved_date' => $this->resolved_date,
            'closed_date' => $this->closed_date,
            'sla_breached' => $this->sla_breached,
            'response_time' => $this->response_time,
            'resolution_time' => $this->resolution_time,
            'estimated_effort' => $this->estimated_effort,
            'actual_effort' => $this->actual_effort,
            'parent_ticket_id' => $this->parent_ticket_id,
            'converted_to_type' => $this->converted_to_type,
            'converted_to_id' => $this->converted_to_id,
            'converted_at' => $this->converted_at,
            'converted_by' => $this->converted_by,
            'conversion_reason' => $this->conversion_reason,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

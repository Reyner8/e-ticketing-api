<?php

namespace App\Http\Resources\Ticket;

use App\Http\Resources\TagResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResourceDetail extends JsonResource
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
            'category' => $this->category->value,
            'priority' => $this->priority->value,
            'status' => $this->status->value,
            'reporter' => $this->reporter ? [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
                'username' => $this->reporter->username,
            ] : null,
            'assigned_user' => $this->assignedUser ? [
                'id' => $this->assignedUser->id,
                'name' => $this->assignedUser->name,
                'username' => $this->assignedUser->username,
            ] : null,
            'assigned_team' => $this->assigned_team ? [
                'value' => $this->assigned_team->value,
                'label' => $this->assigned_team->label()
            ]: null,
            'date_reported' => $this->date_reported?->format('Y-m-d H:i:s'),
            'due_date' => $this->due_date?->format('Y-m-d H:i:s'),
            'resolved_date' => $this->resolved_date?->format('Y-m-d H:i:s'),
            'closed_date' => $this->closed_date?->format('Y-m-d H:i:s'),
            'sla_breached' => $this->sla_breached,
            'time' => [
                'response_time' => $this->response_time,
                'resolution_time' => $this->resolution_time,
            ],
            'effort' => [
                'estimated_effort' => $this->estimated_effort,
                'actual_effort' => $this->actual_effort
            ],
            'parent_ticket_id' => $this->parent_ticket_id,
            'conversion' => $this->isConverted() ? [
                'type' => $this->converted_to_type->value,
                'id' => $this->converted_to_id,
                'at' => $this->converted_at?->format('Y-m-d H:i:s'),
                'reason' => $this->conversion_reason
            ]: null,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

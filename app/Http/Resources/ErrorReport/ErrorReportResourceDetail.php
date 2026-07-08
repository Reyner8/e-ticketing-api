<?php

namespace App\Http\Resources\ErrorReport;

use App\Http\Resources\TagResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorReportResourceDetail extends JsonResource
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
            'category' => [
                'value' => $this->category->value,
                'label' => $this->category->label()
            ],
            'priority' => [
                'value' => $this->priority->value,
                'label' => $this->priority->label()
            ],
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label()
            ],
            'approval' => [
                'status' => $this->approval_status instanceof \App\Enums\ApprovalStatus
                    ? $this->approval_status->value
                    : ($this->approval_status ?? 'pending'),
                'processed_at' => $this->approval_date?->format('Y-m-d H:i:s'),
                'rejection_reason' => $this->rejection_reason,
            ],
            'reporter' => $this->reporter ? [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
                'username' => $this->reporter->username,
            ]: null,
            'assigned_user' => $this->assignedUser ? [
                'id' => $this->assignedUser->id,
                'name' => $this->assignedUser->name,
                'username' => $this->assignedUser->username,
            ]: null,
            'assigned_team' => $this->assigned_team ? [
                'value' => $this->assigned_team->value,
                'label' => $this->assigned_team->label()
            ]: null,
            'date_reported' => $this->date_reported?->format('Y-m-d H:i:s'),
            'start_date' => $this->start_date?->format('Y-m-d H:i:s'),
            'due_date' => $this->due_date?->format('Y-m-d H:i:s'),
            'completion_date' => $this->completion_date?->format('Y-m-d H:i:s'),
            'sla' => [
                'breached' => $this->sla_breached,
                'elapsed' => $this->sla_elapsed,
                'time_remaining' => $this->sla_time_remaining,
            ],
            'effort' => [
                'estimated' => $this->estimated_effort,
                'actual' => $this->actual_effort
            ],
            'source' => [
                'is_direct_input' => $this->is_direct_input,
                'source_ticket_id' => $this->source_ticket_id
            ],
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

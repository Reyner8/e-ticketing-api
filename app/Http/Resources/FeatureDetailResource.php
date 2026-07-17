<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'request_type' => $this->request_type,
            'target_application' => $this->target_application ? [
                'value' => $this->target_application->value,
                'label' => $this->target_application->label(),
            ] : null,
            'priority' => $this->priority,
            'status' => $this->status,
            'approval_status' => $this->approval_status instanceof \App\Enums\ApprovalStatus
                ? $this->approval_status->value
                : $this->approval_status,
            'progress' => $this->progress,
            'reporter_id' => $this->reporter_id,
            'assigned_to_id' => $this->assigned_to_id,
            'assigned_team' => $this->assigned_team,
            'due_date' => $this->due_date,
            'sla_breached' => $this->sla_breached,
            'approved_by' => $this->approved_by,
            'rejection_reason' => $this->rejection_reason,
            'post_implementation_notes' => $this->post_implementation_notes,
            'source_ticket_id' => $this->source_ticket_id,
            'is_direct_input' => $this->is_direct_input,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

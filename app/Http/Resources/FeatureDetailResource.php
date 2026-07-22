<?php

namespace App\Http\Resources;

use App\Models\Application;
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
            'target_application' => Application::toOption(
                is_object($this->target_application)
                    ? $this->target_application->value
                    : $this->target_application
            ),
            'priority' => $this->priority,
            'status' => $this->status,
            'approval_status' => $this->approval_status instanceof \App\Enums\ApprovalStatus
                ? $this->approval_status->value
                : $this->approval_status,
            'progress' => $this->progress,
            'reporter_id' => $this->reporter_id,
            'assigned_to_id' => $this->assigned_to_id,
            'reporter' => $this->whenLoaded('reporter', fn () => $this->reporter ? [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
                'username' => $this->reporter->username,
            ] : null),
            'assigned_user' => $this->whenLoaded('assignee', fn () => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'username' => $this->assignee->username,
            ] : null),
            'assigned_team' => $this->assigned_team ? [
                'value' => $this->assigned_team->value,
                'label' => $this->assigned_team->label(),
            ] : null,
            'due_date' => $this->due_date,
            'sla_breached' => $this->sla_breached,
            'approved_by' => $this->approved_by,
            'rejection_reason' => $this->rejection_reason,
            'post_implementation_notes' => $this->post_implementation_notes,
            'source_ticket_id' => $this->source_ticket_id,
            'is_direct_input' => $this->is_direct_input,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

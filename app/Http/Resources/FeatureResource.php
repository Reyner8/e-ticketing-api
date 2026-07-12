<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ([
            'id' => $this->id,
            'title' => $this->title,
            'request_type' => $this->request_type,
            'priority' => $this->priority,
            'status' => $this->status,
            'progress' => $this->progress,
            'reporter' => $this->reporter ? [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
                'username' => $this->reporter->username,
            ] : null,
            'assigned_user' => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
                'username' => $this->assignee->username,
            ] : null,
            'assigned_team' => $this->assigned_team ? [
                'value' => $this->assigned_team->value,
                'label' => $this->assigned_team->label(),
            ] : null,
            'sla_breached' => (bool) $this->sla_breached,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'date_submitted' => $this->date_submitted,
            'due_date' => $this->due_date,
            'completion_date' => $this->completion_date,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ]);
    }
}

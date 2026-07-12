<?php

namespace App\Http\Resources\ErrorReport;

use App\Http\Resources\TagResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorReportResource extends JsonResource
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
                'label' => $this->assigned_team->label(),
            ] : null,
            'sla_breached' => (bool) $this->sla_breached,
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'date_reported' => $this->date_reported?->format('Y-m-d H:i:s'),
            'due_date' => $this->due_date?->format('Y-m-d H:i:s'),
            'completion_date' => $this->completion_date?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

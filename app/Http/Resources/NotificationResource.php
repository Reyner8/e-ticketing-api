<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'is_read' => $this->is_read,
            'type' => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
            ],
            'priority' => [
                'value' => $this->priority->value,
                'label' => $this->priority->label()
            ],
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->action_url,
            'ticket' => $this->ticket_id ? [
                'id' => $this->ticket_id,
                'title' => $this->ticket?->title,
            ] : null,
            'downtime' => $this->downtime_id ? [
                'id' => $this->downtime_id,
                'title' => $this->downtime?->title,
            ] : null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

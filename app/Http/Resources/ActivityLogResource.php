<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\ActivityAction;

class ActivityLogResource extends JsonResource
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
            'action' => [
                'value' => $this->action->value,
                'label' => $this->action->label(),
            ],
            
            'description' => $this->description,
            'details' => $this->details,

            'performed_by' => $this->performer ? [
                'id' => $this->performer->id,
                'name' => $this->performer->name,
                'username' => $this->performer->username,
            ] : null,

            'target_user' => $this->targetUser ? [
                'id' => $this->targetUser->id,
                'name' => $this->targetUser->name,
                'username' => $this->targetUser->username
            ] : null,

            'performed_at' => $this->performed_at->format('Y-m-d H:i:s'),
            'loggable' => [
                'id' => $this->loggable_id,
                'type' => class_basename($this->loggable_type),
            ],
        ];
    }
}

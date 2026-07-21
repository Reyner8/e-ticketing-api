<?php

namespace App\Http\Resources\ServerRoomInspection;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServerRoomInspectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'inspection_date' => $this->inspection_date?->format('Y-m-d'),
            'inspector' => $this->inspector ? [
                'id' => $this->inspector->id,
                'name' => $this->inspector->name,
                'username' => $this->inspector->username,
            ] : null,
            'inspection_type' => [
                'value' => $this->inspection_type->value,
                'label' => $this->inspection_type->label(),
            ],
            'checklist_items' => $this->checklist_items,
            'conclusion' => [
                'value' => $this->conclusion->value,
                'label' => $this->conclusion->label(),
            ],
            'follow_up' => $this->follow_up,
            'escalation' => $this->escalation ? [
                'value' => $this->escalation->value,
                'label' => $this->escalation->label(),
            ] : null,
            'notes' => $this->notes,
            'created_by' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'username' => $this->creator->username,
            ] : null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

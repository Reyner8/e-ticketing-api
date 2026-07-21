<?php

namespace App\Http\Resources\ServerRoomVisitor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServerRoomVisitorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_at' => $this->entry_at?->format('Y-m-d H:i:s'),
            'exit_at' => $this->exit_at?->format('Y-m-d H:i:s'),
            'visitor_name' => $this->visitor_name,
            'unit_or_vendor' => $this->unit_or_vendor,
            'identity_document' => $this->identity_document,
            'purpose' => $this->purpose,
            'escorted_by' => $this->escort ? [
                'id' => $this->escort->id,
                'name' => $this->escort->name,
                'username' => $this->escort->username,
            ] : null,
            'notes' => $this->notes,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
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

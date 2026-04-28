<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusHistoryResource extends JsonResource
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
            'statusable' => [
                'type' => $this->statusable_type,
                'id' => $this->statusable_id,
            ],
            'previous_status' => $this->previous_status,
            'new_status' => $this->new_status,
            'changed_by' => $this->changer ? [
                'id' => $this->changer->id,
                'name' => $this->changer->name,
                'username' => $this->changer->username,
            ]: null,
            'reason' => $this->reason,
            'notes' => $this->notes,
        ];
    }
}

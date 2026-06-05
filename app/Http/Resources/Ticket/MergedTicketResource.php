<?php

namespace App\Http\Resources\Ticket;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MergedTicketResource extends JsonResource
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
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'reporter' => $this->reporter ? [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
                'username' => $this->reporter->username,
            ]: null,
            'merged_at' =>  $this->pivot?->merged_at?->format('Y-m-d H:i:s'),
            'merged_by' => $this->pivot?->merged_by,
        ];
    }
}

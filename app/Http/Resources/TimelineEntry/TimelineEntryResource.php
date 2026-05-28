<?php

namespace App\Http\Resources\TimelineEntry;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelineEntryResource extends JsonResource
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
            'feature_request_id' => $this->feature_request_id,
            'phase' => $this->phase->label(),
            'title' => $this->title,
            'description' => $this->description,
        ];
    }
}

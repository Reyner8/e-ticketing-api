<?php

namespace App\Http\Resources\CalendarEvent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarEventResource extends JsonResource
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
            'start' => $this->start->format('Y-m-d'),
            'end' => $this->end->format('Y-m-d'),
            'type' => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
            ],
            'description' => $this->description,
            'color' => $this->color,
            'all_day' => $this->all_day,
            'recurring' => $this->isRecurring() ? [
                'frequency' => $this->recurring_frequency->value,
                'label' => $this->recurring_frequency->label(),
                'interval' => $this->recurring_interval->value,
                'end_date' => $this->recurring_end_date->value,
            ]: null,
            'created_by' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'username' => $this->creator->username,
            ]: null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DowntimeComponentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category' => [
                'value' => $this->category?->value ?? $this->category,
                'label' => $this->category?->label() ?? (string) $this->category,
            ],
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'default_affected_components' => $this->whenLoaded('defaultAffectedComponents', function () {
                return $this->defaultAffectedComponents->map(fn ($component) => [
                    'id' => $component->id,
                    'code' => $component->code,
                    'name' => $component->name,
                    'category' => [
                        'value' => $component->category?->value ?? $component->category,
                        'label' => $component->category?->label() ?? (string) $component->category,
                    ],
                    'is_active' => (bool) $component->is_active,
                ])->values();
            }),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'username' => $this->creator->username,
            ] : null),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

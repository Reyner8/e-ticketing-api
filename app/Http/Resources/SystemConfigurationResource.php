<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemConfigurationResource extends JsonResource
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
            'config_key' => $this->config_key,
            'config_value' => $this->config_value,
            'description' => $this->description,
            'updated_by' => $this->updater ? [
                'id' => $this->updater->id,
                'name' => $this->updater->name,
                'username' => $this->updater->username
            ]: null,
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

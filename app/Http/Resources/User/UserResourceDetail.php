<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResourceDetail extends JsonResource
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
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'role' => $this->role->value,
            'team' => $this->team ? [
                'value' => $this->team->value,
                'label' => $this->team->label()
            ]: null,
            'is_active' => $this->is_active,
            'last_login' => $this->last_login?->format('Y-m-d H:i:s'),
            'preferences' => $this->preferences,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}

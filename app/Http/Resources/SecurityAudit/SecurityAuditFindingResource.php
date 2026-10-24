<?php

namespace App\Http\Resources\SecurityAudit;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SecurityAuditFindingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'security_audit_id' => $this->security_audit_id,
            'description' => $this->description,
            'severity' => [
                'value' => $this->severity->value,
                'label' => $this->severity->label(),
            ],
            'recommendation' => $this->recommendation,
            'corrective_action' => $this->corrective_action,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'due_date' => $this->due_date?->format('Y-m-d'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

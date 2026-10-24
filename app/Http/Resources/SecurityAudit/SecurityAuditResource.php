<?php

namespace App\Http\Resources\SecurityAudit;

use App\Models\SecurityAuditFinding;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SecurityAuditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
            ],
            'audit_date' => $this->audit_date?->format('Y-m-d'),
            'period' => $this->period,
            'performed_by' => $this->performer ? [
                'id' => $this->performer->id,
                'name' => $this->performer->name,
                'username' => $this->performer->username,
            ] : null,
            'external_auditor' => $this->external_auditor,
            'scope' => $this->scope,
            'summary' => $this->summary,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'findings' => $this->whenLoaded(
                'findings',
                fn () => SecurityAuditFindingResource::collection($this->findings)
            ),
            'findings_count' => $this->whenCounted('findings'),
            'open_findings_count' => $this->whenCounted('open_findings_count'),
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

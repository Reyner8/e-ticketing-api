<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\ApprovalStatus;

class ApprovalResource extends JsonResource
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
            'approval' => [
                'status' => $this->approval_status instanceof ApprovalStatus
                ? $this->approval_status->value
                : $this->approval_status,

                'label' => $this->approval_status instanceof ApprovalStatus
                ? $this->approval_status->label()
                : $this->approval_status,

                'processed_by' => $this->approver ? [
                    'id' => $this->approver->id,
                    'name' => $this->approver->name,
                    'username' => $this->approver->username,
                ]: null,

                'processed_at' => $this->approval_date?->format('Y-m-d H:i:s'),
                'rejection_reason' => $this->rejection_reason,
            ],
        ];
    }
}

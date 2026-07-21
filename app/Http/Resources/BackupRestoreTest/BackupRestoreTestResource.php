<?php

namespace App\Http\Resources\BackupRestoreTest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackupRestoreTestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'test_date' => $this->test_date?->format('Y-m-d'),
            'performed_by' => $this->performer ? [
                'id' => $this->performer->id,
                'name' => $this->performer->name,
                'username' => $this->performer->username,
            ] : null,
            'application_system' => $this->application_system,
            'restore_type' => [
                'value' => $this->restore_type->value,
                'label' => $this->restore_type->label(),
            ],
            'backup_datetime' => $this->backup_datetime?->format('Y-m-d H:i:s'),
            'backup_source' => $this->backup_source,
            'test_environment' => $this->test_environment,
            'result' => [
                'value' => $this->result->value,
                'label' => $this->result->label(),
            ],
            'notes' => $this->notes,
            'follow_up' => $this->follow_up,
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

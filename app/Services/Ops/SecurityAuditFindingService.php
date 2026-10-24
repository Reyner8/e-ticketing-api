<?php

namespace App\Services\Ops;

use App\Enums\FindingStatus;
use App\Enums\FindingSeverity;
use App\Models\SecurityAudit;
use App\Models\SecurityAuditFinding;
use Illuminate\Support\Collection;

class SecurityAuditFindingService
{
    public function getAll(SecurityAudit $audit): Collection
    {
        return $audit->findings()->latest('created_at')->get();
    }

    public function store(SecurityAudit $audit, array $data): SecurityAuditFinding
    {
        $data['security_audit_id'] = $audit->id;
        $data['severity'] = FindingSeverity::from($data['severity'])->value;
        $data['status'] = isset($data['status'])
            ? FindingStatus::from($data['status'])->value
            : FindingStatus::Open->value;

        if ($data['status'] === FindingStatus::Completed->value) {
            $data['completed_at'] = now();
        }

        return SecurityAuditFinding::create($data);
    }

    public function update(SecurityAuditFinding $finding, array $data): SecurityAuditFinding
    {
        if (isset($data['severity'])) {
            $data['severity'] = FindingSeverity::from($data['severity'])->value;
        }

        $newStatus = isset($data['status']) ? FindingStatus::from($data['status'])->value : null;

        if ($newStatus !== null) {
            if ($newStatus === FindingStatus::Completed->value && $finding->completed_at === null) {
                $data['completed_at'] = now();
            } elseif ($newStatus !== FindingStatus::Completed->value) {
                $data['completed_at'] = null;
            }
            $data['status'] = $newStatus;
        }

        $finding->update($data);

        return $finding->fresh();
    }

    public function delete(SecurityAuditFinding $finding): void
    {
        $finding->delete();
    }
}

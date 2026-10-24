<?php

namespace App\Services\Ops;

use App\Enums\FindingStatus;
use App\Enums\SecurityAuditStatus;
use App\Enums\SecurityAuditType;
use App\Models\SecurityAudit;
use App\Support\SequentialIdGenerator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class SecurityAuditService
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return SecurityAudit::query()
            ->with(['performer:id,name,username', 'creator:id,name,username'])
            ->withCount([
                'findings',
                'findings as open_findings_count' => fn ($q) => $q->where('status', FindingStatus::Open->value),
            ])
            ->when(
                isset($filters['type']),
                fn ($q) => $q->where('type', $filters['type'])
            )
            ->when(
                isset($filters['status']),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->when(
                isset($filters['from']),
                fn ($q) => $q->whereDate('audit_date', '>=', $filters['from'])
            )
            ->when(
                isset($filters['to']),
                fn ($q) => $q->whereDate('audit_date', '<=', $filters['to'])
            )
            ->when(
                isset($filters['search']),
                function ($q) use ($filters) {
                    $term = '%'.$filters['search'].'%';

                    return $q->where(function ($inner) use ($term) {
                        $inner->where('id', 'like', $term)
                            ->orWhere('title', 'like', $term)
                            ->orWhere('scope', 'like', $term)
                            ->orWhere('external_auditor', 'like', $term);
                    });
                }
            )
            ->latest('audit_date')
            ->latest('id')
            ->paginate(min($perPage, 50));
    }

    public function loadRecord(SecurityAudit $audit): SecurityAudit
    {
        return $audit->load([
            'performer:id,name,username',
            'creator:id,name,username',
            'findings' => fn ($q) => $q->latest('created_at'),
        ]);
    }

    public function store(array $data): SecurityAudit
    {
        $data['id'] = SequentialIdGenerator::next(SecurityAudit::class, 'AUD');
        $data['performed_by'] = $data['performed_by'] ?? Auth::id();
        $data['created_by'] = Auth::id();
        $data['type'] = SecurityAuditType::from($data['type'])->value;
        $data['status'] = isset($data['status'])
            ? SecurityAuditStatus::from($data['status'])->value
            : SecurityAuditStatus::Planned->value;

        return $this->loadRecord(SecurityAudit::create($data));
    }

    public function update(SecurityAudit $audit, array $data): SecurityAudit
    {
        if (isset($data['type'])) {
            $data['type'] = SecurityAuditType::from($data['type'])->value;
        }
        if (isset($data['status'])) {
            $data['status'] = SecurityAuditStatus::from($data['status'])->value;
        }

        $audit->update($data);

        return $this->loadRecord($audit->fresh());
    }

    public function delete(SecurityAudit $audit): void
    {
        $audit->delete();
    }
}

<?php

namespace App\Models;

use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'security_audit_id',
    'description',
    'severity',
    'recommendation',
    'corrective_action',
    'status',
    'due_date',
    'completed_at',
])]
class SecurityAuditFinding extends Model
{
    protected $casts = [
        'severity' => FindingSeverity::class,
        'status' => FindingStatus::class,
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(SecurityAudit::class, 'security_audit_id');
    }
}

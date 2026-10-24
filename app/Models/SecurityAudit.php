<?php

namespace App\Models;

use App\Enums\SecurityAuditStatus;
use App\Enums\SecurityAuditType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'id',
    'title',
    'type',
    'audit_date',
    'period',
    'performed_by',
    'external_auditor',
    'scope',
    'summary',
    'status',
    'created_by',
])]
class SecurityAudit extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'type' => SecurityAuditType::class,
        'status' => SecurityAuditStatus::class,
        'audit_date' => 'date',
    ];

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(SecurityAuditFinding::class, 'security_audit_id');
    }
}

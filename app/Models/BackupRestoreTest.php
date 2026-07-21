<?php

namespace App\Models;

use App\Enums\RestoreTestResult;
use App\Enums\RestoreType;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id',
    'test_date',
    'performed_by',
    'application_system',
    'restore_type',
    'backup_datetime',
    'backup_source',
    'test_environment',
    'result',
    'notes',
    'follow_up',
    'created_by',
])]
class BackupRestoreTest extends Model
{
    use HasAttachments;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'test_date' => 'date',
        'backup_datetime' => 'datetime',
        'restore_type' => RestoreType::class,
        'result' => RestoreTestResult::class,
    ];

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

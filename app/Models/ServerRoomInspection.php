<?php

namespace App\Models;

use App\Enums\InspectionConclusion;
use App\Enums\InspectionEscalation;
use App\Enums\InspectionType;
use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id',
    'inspection_date',
    'inspector_id',
    'inspection_type',
    'checklist_items',
    'conclusion',
    'follow_up',
    'escalation',
    'notes',
    'created_by',
])]
class ServerRoomInspection extends Model
{
    use HasAttachments;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $casts = [
        'inspection_date' => 'date',
        'inspection_type' => InspectionType::class,
        'conclusion' => InspectionConclusion::class,
        'escalation' => InspectionEscalation::class,
        'checklist_items' => 'array',
    ];

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

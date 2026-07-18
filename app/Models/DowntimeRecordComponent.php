<?php

namespace App\Models;

use App\Enums\DowntimeComponentRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DowntimeRecordComponent extends Model
{
    protected $fillable = [
        'downtime_id',
        'component_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => DowntimeComponentRole::class,
        ];
    }

    public function downtimeRecord(): BelongsTo
    {
        return $this->belongsTo(DowntimeRecord::class, 'downtime_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(DowntimeComponent::class, 'component_id');
    }
}

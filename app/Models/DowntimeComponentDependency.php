<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DowntimeComponentDependency extends Model
{
    protected $fillable = [
        'source_component_id',
        'affected_component_id',
    ];

    public function sourceComponent(): BelongsTo
    {
        return $this->belongsTo(DowntimeComponent::class, 'source_component_id');
    }

    public function affectedComponent(): BelongsTo
    {
        return $this->belongsTo(DowntimeComponent::class, 'affected_component_id');
    }
}

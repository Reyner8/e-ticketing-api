<?php

namespace App\Models;

use App\Enums\DowntimeComponentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DowntimeComponent extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => DowntimeComponentCategory::class,
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function defaultAffectedComponents(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'downtime_component_dependencies',
            'source_component_id',
            'affected_component_id'
        )->withTimestamps();
    }

    public function defaultSourceComponents(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'downtime_component_dependencies',
            'affected_component_id',
            'source_component_id'
        )->withTimestamps();
    }

    public function recordLinks(): HasMany
    {
        return $this->hasMany(DowntimeRecordComponent::class, 'component_id');
    }

    public function isReferenced(): bool
    {
        return $this->recordLinks()->exists();
    }
}

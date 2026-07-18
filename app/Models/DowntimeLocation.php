<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DowntimeLocation extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function downtimeRecords(): BelongsToMany
    {
        return $this->belongsToMany(
            DowntimeRecord::class,
            'downtime_record_locations',
            'location_id',
            'downtime_id'
        )->withTimestamps();
    }

    public function isReferenced(): bool
    {
        return $this->downtimeRecords()->exists();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function downtimeRecords(): HasMany
    {
        return $this->hasMany(DowntimeRecord::class, 'location_id');
    }

    public function isReferenced(): bool
    {
        return $this->downtimeRecords()->exists();
    }
}

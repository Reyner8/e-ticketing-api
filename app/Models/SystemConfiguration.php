<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;

#[Fillable([
    'config_key',
    'config_value',
    'description',
    'updated_by',
    'updated_at',
])]
#[WithoutTimestamps]

class SystemConfiguration extends Model
{
    public static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->updated_at)) {
                $model->updated_at = now();
            }
        });
        
        static::updating(function (self $model) {
            $model->updated_at = now();
        });
    }

    protected $casts = [
        'config_value' => 'array',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helpers
    public static function get(string $key, mixed $default = null): mixed
    {
        $config = static::where('config_key', $key)->first();

        return $config ? $config->config_value : $default;
    }

    public static function set(
        string $key,
        mixed $value,
        ?string $description = null,
        ?string $updatedBy = null
    ): self {
        return static::updateOrCreate(
            ['config_key' => $key],
            [
                'config_value' => $value,
                'description' => $description,
                'updated_by' => $updatedBy,
                'updated_at' => now(),
            ]
        );
    }

    // Scopes
    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('config_key', $key);
    }
}

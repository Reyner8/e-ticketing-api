<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isReferenced(): bool
    {
        return FeatureRequest::query()->where('target_application', $this->code)->exists()
            || BackupRestoreTest::query()->where('application_system', $this->code)->exists();
    }

    /**
     * @return array{value: string, label: string}|null
     */
    public static function toOption(?string $code): ?array
    {
        if ($code === null || $code === '') {
            return null;
        }

        static $labelCache = [];
        static $tableReady = null;

        if ($tableReady === null) {
            try {
                $tableReady = \Illuminate\Support\Facades\Schema::hasTable('applications');
            } catch (\Throwable) {
                $tableReady = false;
            }
        }

        if (! $tableReady) {
            return [
                'value' => $code,
                'label' => $code,
            ];
        }

        if (! array_key_exists($code, $labelCache)) {
            try {
                $labelCache[$code] = static::query()->where('code', $code)->value('name');
            } catch (\Throwable) {
                $labelCache[$code] = null;
            }
        }

        return [
            'value' => $code,
            'label' => $labelCache[$code] ?: $code,
        ];
    }
}

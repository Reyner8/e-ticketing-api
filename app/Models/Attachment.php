<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

use function Symfony\Component\Clock\now;

#[Fillable([
    'name',
    'size',
    'type',
    'url',
    'attachable_id',
    'attachable_type',
    'uploaded_by',
    'uploaded_at',
])]

#[WithoutTimestamps]

class Attachment extends Model
{
    use HasActivityLog;
    
    protected $casts = [
        'size' => 'integer',
        'uploaded_at' => 'datetime', 
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uploaded_at)) {
                $model->uploaded_at =   now();
            }
        }); 
    }

    // Relations
    public function attachable()
    {
        return $this->morphTo();
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Helpers
    public function getFormattedSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        $index = 0;

        while ($size >= 1024 && $index < count($units) - 1) {
            $size /= 1024;
            $index++;
        }

        return round($size, 2) . ' ' . $units[$index]; 
    }
}

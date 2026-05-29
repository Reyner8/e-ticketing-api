<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'name',
    'created_at'
])]

#[WithoutTimestamps]

class Tag extends Model
{
    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->created_at)) {
                $model->created_at = now(); 
            }
        });
    }

    // Relations
    public function tickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_tags', 'tag_id', 'ticket_id');
    }

    public function featureRequests(): BelongsToMany
    {
        return $this->belongsToMany(FeatureRequest::class, 'feature_request_tags', 'tag_id', 'feature_request_id');
    }

    public function errorReports(): BelongsToMany
    {
        return $this->belongsToMany(ErrorReport::class, 'error_report_tags', 'tag_id', 'error_report_id');
    }
}

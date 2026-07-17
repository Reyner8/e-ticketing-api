<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

#[WithoutTimestamps]
#[Fillable([
    'statusable_type',
    'statusable_id',
    'previous_status',
    'new_status',
    'changed_by',
    'changed_at',
    'effective_at',
    'reason',
    'notes',
])]

class StatusHistory extends Model
{
    use HasActivityLog;

    protected $casts = [
        'changed_at' => 'datetime',
        'effective_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $model) {
            $now = now();
            if (empty($model->changed_at)) {
                $model->changed_at = $now;
            }
            if (empty($model->effective_at)) {
                $model->effective_at = $model->changed_at ?? $now;
            }
        });
    }

    public function statusable()
    {
        return $this->morphTo();
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function errorReport()
    {
        return $this->belongsTo(ErrorReport::class, 'error_report_id');
    }

    public function featureRequest()
    {
        return $this->belongsTo(FeatureRequest::class, 'feature_request_id');
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

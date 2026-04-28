<?php

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasComments;
use App\Traits\HasStatusHistory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'id',
    'title',
    'description',
    'category',
    'priority',
    'status',
    'reporter_id',
    'assigned_to_id',
    'assigned_team',
    'date_reported',
    'due_date',
    'resolved_date',
    'closed_date',
    'sla_breached',
    'response_time',
    'resolution_time',
    'estimated_effort',
    'actual_effort',
    'parent_ticket_id',
    'converted_to_type',
    'converted_to_id',
    'converted_at',
    'converted_by',
    'conversion_reason',
])]
class Ticket extends Model
{
    use HasComments, HasAttachments, HasStatusHistory;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'date_reported' => 'datetime',
        'due_date' => 'datetime',
        'resolved_date' => 'datetime',
        'closed_date' => 'datetime',
        'converted_at' => 'datetime',
        'sla_breached' => 'boolean',
        'response_time' => 'integer',
        'resolution_time' => 'integer',
        'estimated_effort' => 'integer',
        'actual_effort' => 'integer',
    ];

    public const CATEGORIES = [
        'software_bug',
        'feature_request',
        'network_issue',
        'hardware_problem',
        'system_error',
        'performance_issue',
    ];

    public const PRIORITIES = [
        'low',
        'medium',
        'high',
        'critical',
    ];

    public const STATUSES = [
        'draft',
        'pending_approval',
        'assigned',
        'in_progress',
        'waiting_for_user',
        'resolved',
        'closed',
        'converted',

    ];

    public const ASSIGNED_TEAMS = [
        'programmer',
        'network',
        'hardware',
    ];

    public const CONVERTED_TO_TYPES = [
        'error_report',
        'feature_request',
    ];

    // Relations
    public function reportedTicket()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignedTicket()
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function convertedBy()
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function parentTicket()
    {
        return $this->belongsTo(Ticket::class, 'parent_ticket_id', 'id');
    }

    public function childTicket()
    {
        return $this->hasMany(Ticket::class, 'parent_ticket_id', 'id');
    }

    public function featureRequest()
    {
        return $this->hasOne(FeatureRequest::class, 'source_ticket_id');
    }

    public function errorReport()
    {
        return $this->hasOne(ErrorReport::class, 'source_ticket_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function notification()
    {
        return $this->hasMany(Notification::class, 'ticket_id');
    }

    // helpers
    public function isConverted()
    {
        return $this->status === 'converted';
    }

    public function canBeConverted()
    {
        return in_array($this->status, [
            'pending_approval',
            'assigned',
            'in_progress',
            'waiting_for_user'
        ]);
    }

    public function convertedUrl()
    {
        if (!$this->isConverted()) return null;

        return match ($this->converted_to_type) {
            'error_report' => route('error-report.show', $this->converted_to_id),
            'feature_request' => route('feature-request.show', $this->converted_to_id),
            'default' => null
        };
    }
}

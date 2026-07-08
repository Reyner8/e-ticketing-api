<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\ErrorReportStatus;
use App\Enums\FeatureRequestStatus;
use App\Enums\TicketStatus;
use App\Models\ErrorReport;
use App\Models\FeatureRequest;
use App\Models\Ticket;
use App\Services\Log\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ApprovalStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function __construct(
        private readonly ActivityLogService $logService
    ) {}
    
    public function approve(Model $resource): Model
    {
        if ($resource->isApproved()) {
            throw ValidationException::withMessages([
                'approval_status' => [
                    'Resource has already been approved'
                ]
            ]);
        }

        $this->guardNotApprovable($resource);

        DB::transaction(function () use ($resource) {
            $updates = [
                'approval_status' => ApprovalStatus::Approved->value,
                'approved_by' => Auth::id(),
                'approval_date' => Carbon::now(),
                'rejection_reason' => null,
            ];

            if ($resource instanceof Ticket) {
                $updates['status'] = TicketStatus::Assigned->value;
            }

            if ($resource instanceof FeatureRequest) {
                $updates['status'] = FeatureRequestStatus::Approved->value;
            }

            if ($resource instanceof ErrorReport) {
                $updates['status'] = ErrorReportStatus::Assigned->value;
            }

            $resource->update($updates);

            // log
            $this->logService->log(
                loggable: $resource,
                action: ActivityAction::Updated,
                description: class_basename($resource) . ' was approved.',
                performedBy: Auth::id(),
                details: array_filter([
                    'approval_status' => ApprovalStatus::Approved->value,
                    'approved_by' => Auth::id()
                ])
            );
        });

        return $resource->load('approver');
    }

    public function reject(Model $resource, string $reason): Model
    {
        if ($resource->isRejected()) {
            throw ValidationException::withMessages([
                'approval_status' => [
                    'Resource has already been rejected'
                ]
            ]);
        }

        $this->guardNotApprovable($resource);

        DB::transaction(function () use ($resource, $reason) {
            $updates = [
                'approval_status' => ApprovalStatus::Rejected->value,
                'approved_by' => Auth::id(),
                'approval_date' => Carbon::now(),
                'rejection_reason' => $reason,
            ];

            if ($resource instanceof Ticket) {
                $updates['status'] = TicketStatus::Closed->value;
                $updates['closed_date'] = Carbon::now();
            }

            if ($resource instanceof FeatureRequest) {
                $updates['status'] = FeatureRequestStatus::Rejected->value;
            }

            $resource->update($updates);

            // log
            $this->logService->log(
                loggable: $resource,
                action: ActivityAction::Updated,
                description: class_basename($resource) . ' was rejected.',
                performedBy: Auth::id(),
                details: [
                    'approval_status' => ApprovalStatus::Rejected->value,
                    'rejected_by' => Auth::id(),
                    'rejection_reason' => $reason
                ]
            );
        });

        return $resource->load('approver');
    }

    // Helpers
    private function guardNotApprovable(Model $resource): void
    {
        if (! $resource->isApprovable()) {
            $currentStatus = $resource->approval_status instanceof ApprovalStatus
                ? $resource->approval_status->value
                : $resource->approval_status;

            throw ValidationException::withMessages([
                'approval_status' => [
                    "Resource with approval status '{$currentStatus}' cannot be processed."
                ]
            ]);
        }
    }
}

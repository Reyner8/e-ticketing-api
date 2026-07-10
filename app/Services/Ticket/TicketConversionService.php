<?php

namespace App\Services\Ticket;

use App\Enums\ConversionTypes;
use App\Enums\ErrorReportStatus;
use App\Enums\FeatureRequestStatus;
use App\Enums\TicketStatus;
use App\Exceptions\ConversionFailedException;
use App\Models\ErrorReport;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Exceptions\TicketAlreadyConvertedException;
use App\Exceptions\TicketCannotBeConvertedException;
use App\Models\FeatureRequest;
use App\Services\Attachment\AttachmentService;
use App\Services\ConversionHistoryService;
use App\Services\Log\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Database\QueryException;

class TicketConversionService
{
    public function __construct(
        private readonly ActivityLogService $logService,
        private readonly NotificationService $notificationService,
        private readonly ConversionHistoryService $historyService,
        private readonly AttachmentService $attachmentService,
    ) {}

    public function convertToErrorReport(string $ticketId, array $data): ErrorReport
    {
        return DB::transaction(function () use ($ticketId, $data) {

            $ticket = Ticket::lockForUpdate()->findOrFail($ticketId);

            $this->validateConversion($ticket);

            $data = array_merge([
                'title' => $ticket->title,
                'description' => $ticket->description,
                'priority' => $ticket->priority
            ], $data);

            try {
                $errorReport = ErrorReport::create([
                    'id' => $this->generateCode('ERR'),
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'category' => $data['category'],
                    'priority' => $data['priority'],
                    'status' => ErrorReportStatus::PendingApproval,
                    'reporter_id' => $ticket->reporter_id,
                    'assigned_to_id' => $ticket->assigned_to_id,
                    'assigned_team' => $ticket->assigned_team,
                    'date_reported' => $ticket->date_reported,
                    'start_date' => $data['start_date'] ?? null,
                    'due_date' => $data['due_date'] ?? null,
                    'completion_date' => $data['completion_date'] ?? null,
                    'estimated_effort' => $data['estimated_effort'] ?? null,
                    'actual_effort' => $data['actual_effort'] ?? null,
                    'sla_time_elapsed' => $data['sla_time_elapsed'] ?? null,
                    'sla_time_remaining' => $data['sla_time_remaining'] ?? null,
                    'sla_breached' => $data['sla_breached'] ?? false,
                    'source_ticket_id' => $ticket->id,
                    'is_direct_input' => false,
                ]);

                // carry original evidence (e.g. public submission screenshots)
                $this->attachmentService->copyToResource(
                    source: $ticket,
                    target: $errorReport,
                    copiedBy: Auth::id() ?? $ticket->reporter_id
                );

                $this->markTicketAsConverted(
                    ticket: $ticket,
                    conversionType: ConversionTypes::ErrorReport,
                    convertedId: $errorReport->id,
                    reason: $data['conversion_reason']
                );

                // conversion history
                $this->historyService->record(
                    ticket: $ticket,
                    targetType: ConversionTypes::ErrorReport,
                    targetId: $errorReport->id,
                    reason: $data['conversion_reason'] ?? null,
                    notes: $data['notes'] ?? null,
                );

                // log converted
                $this->logService->logConverted(
                    loggable: $ticket,
                    fromType: 'ticket',
                    toType: 'error_report'
                );

                // reporter notification
                $this->notificationService->notifyTicketConverted(
                    userId: $ticket->reporter_id,
                    ticket: $ticket,
                    toType: 'error_report'
                );

                // assignee notification
                if ($ticket->assigned_to_id && $ticket->assigned_to_id !== Auth::id()) {
                    $this->notificationService->notifyTicketConverted(
                        userId: $ticket->assigned_to_id,
                        ticket: $ticket,
                        toType: 'error_report'
                    );
                }

                return $errorReport;

            } catch (TicketAlreadyConvertedException | TicketCannotBeConvertedException $e) {
                throw $e;
            } catch (QueryException $e) {
                throw new ConversionFailedException(
                    ticketId: $ticket->id,
                    context: [
                        'sql' => $e->getSql(),
                        'message' => $e->getMessage()
                    ]
                );
            }
        });
    }

    public function convertToFeatureRequest(string $ticketId, array $data): FeatureRequest
    {
        return DB::transaction(function () use ($ticketId, $data) {

            $ticket = Ticket::lockForUpdate()->findOrFail($ticketId);

            $this->validateConversion($ticket);

            $data = array_merge([
                'title' => $ticket->title,
                'description' => $ticket->description,
                'priority' => $ticket->priority
            ], $data);
            
            try {
                $featureRequest = FeatureRequest::create([
                    'id' => $this->generateCode('FR'),
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'request_type' => $data['request_type'],
                    'priority' => $data['priority'],
                    'status' => FeatureRequestStatus::PendingApproval,
                    'progress' => 0,
                    'reporter_id' => $ticket->reporter_id,
                    'assigned_to_id' => $ticket->assigned_to_id,
                    'date_submitted' => $ticket->date_reported,
                    'approval_date' => $data['approval_date'] ?? null,
                    'assignment_date' => $data['assignment_date'] ?? null,
                    'start_date' => $data['start_date'] ?? null,
                    'due_date' => $data['due_date'] ?? null,
                    'completion_date' => $data['completion_date'] ?? null,
                    'review_date' => $data['review_date'] ?? null,
                    'estimated_effort' => $data['estimated_effort'] ?? null,
                    'actual_effort' => $data['actual_effort'] ?? null,
                    'sla_time_elapsed' => $data['sla_time_elapsed'] ?? null,
                    'sla_time_remaining' => $data['sla_time_remaining'] ?? null,
                    'sla_breached' => $data['sla_breached'] ?? false,
                    'approved_by' => $data['approved_by'] ?? null,
                    'rejection_reason' => $data['rejection_reason'] ?? null,
                    'roi_impact' => $data['roi_impact'] ?? null,
                    'quality_impact' => $data['quality_impact'] ?? null,
                    'post_implementation_notes' => $data['post_implementation_notes'] ?? null,
                    'source_ticket_id' => $ticket->id,
                    'is_direct_input' => false,
                ]);

                // carry original evidence (e.g. public submission screenshots)
                $this->attachmentService->copyToResource(
                    source: $ticket,
                    target: $featureRequest,
                    copiedBy: Auth::id() ?? $ticket->reporter_id
                );

                $this->markTicketAsConverted(
                    ticket: $ticket,
                    conversionType: ConversionTypes::FeatureRequest,
                    convertedId: $featureRequest->id,
                    reason: $data['conversion_reason']
                );

                // conversion history
                $this->historyService->record(
                    ticket: $ticket,
                    targetType: ConversionTypes::FeatureRequest,
                    targetId: $featureRequest->id,
                    reason: $data['conversion_reason'] ?? null,
                    notes: $data['notes'] ?? null,
                );

                // log converted
                $this->logService->logConverted(
                    loggable: $ticket,
                    fromType: 'ticket',
                    toType: 'feature_request'
                );

                // reporter notification
                $this->notificationService->notifyTicketConverted(
                    userId: $ticket->reporter_id,
                    ticket: $ticket,
                    toType: 'feature_request'
                );

                // assignee notification
                if ($ticket->assigned_to_id && $ticket->assigned_to_id !== Auth::id()) {
                    $this->notificationService->notifyTicketConverted(
                        userId: $ticket->assigned_to_id,
                        ticket: $ticket,
                        toType: 'feature_request'
                    );
                }

                return $featureRequest;

            } catch (TicketAlreadyConvertedException | TicketCannotBeConvertedException $e) {
                throw $e;
            } catch (QueryException $e) {
                throw new ConversionFailedException(
                    ticketId: $ticket->id,
                    context: [
                        'sql' => $e->getSql(),
                        'message' => $e->getMessage()
                    ]
                );
            }
        });
    }

    // private helper
    private function generateCode(string $prefix): string
    {
        $year = now()->year;
        $model = $prefix === 'ERR' ? ErrorReport::class : FeatureRequest::class;

        $lastRecord = $model::whereYear('created_at', $year)
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->id, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%d-%03d', $prefix, $year, $nextNumber);
    }

    private function validateConversion(Ticket $ticket): void
    {
        if ($ticket->isConverted()) {
            throw new TicketAlreadyConvertedException(
                ticketId: $ticket->id,
                convertedToType: $ticket->converted_to_type,
                convertedToId: $ticket->converted_to_id,
                convertedAt: $ticket->converted_at
            );
        }

        if (! $ticket->canBeConverted()) {
            throw new TicketCannotBeConvertedException(
                ticketId: $ticket->id,
                currentStatus: $ticket->status,
                approvalStatus: $ticket->approval_status
            );
        }
    }

    private function markTicketAsConverted(
        Ticket $ticket,
        ConversionTypes $conversionType,
        string $convertedId,
        string $reason
    ): void {
        $ticket->update([
            'status' => TicketStatus::Converted,
            'converted_to_type' => $conversionType,
            'converted_to_id' => $convertedId,
            'converted_at' => now(),
            'converted_by' => Auth::id(),
            'conversion_reason' => $reason
        ]);
    }
}

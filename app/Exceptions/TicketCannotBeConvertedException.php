<?php

namespace App\Exceptions;

use App\Enums\ApprovalStatus;
use App\Enums\TicketStatus;
use Exception;
use Illuminate\Http\JsonResponse;
class TicketCannotBeConvertedException extends Exception
{

    public function __construct(
        private readonly string $ticketId,
        private readonly TicketStatus $currentStatus,
        private readonly ApprovalStatus $approvalStatus,
        private readonly array $allowedStatuses = [
            TicketStatus::Draft,
            TicketStatus::PendingApproval,
            TicketStatus::Assigned,
            TicketStatus::InProgress,
            TicketStatus::WaitingForUser,
        ],
    ) {
        $allowed = implode(',', array_map(
            fn (TicketStatus $status) => $status->value,
            $allowedStatuses 
        ));

        parent::__construct(
            "Ticket {$ticketId} can not be converted. " .
            "Current status: {$currentStatus->value}, " .
            "approval status: {$approvalStatus->value}, " .
            "Ticket must be approved and have one of the following statuses: {$allowed}."
        );
    }
    /**
     * Report the exception.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data' => [
                'ticket_id' => $this->ticketId,
                'current_status' => $this->currentStatus->value,
                'approval_status' => $this->approvalStatus->value,
                'required_approval_status' => ApprovalStatus::Approved->value,
                'allowed_statuses' => array_map(
                    fn (TicketStatus $status) => $status->value,
                    $this->allowedStatuses
                )
            ]
        ], 422);
    }

    // getter
    public function getTicketId(): string
    {
        return $this->ticketId;
    }
    public function getCurrentStatus(): TicketStatus
    {
        return $this->currentStatus;
    }
    public function getAllowedStatuses(): array
    {
        return $this->allowedStatuses;
    }
}

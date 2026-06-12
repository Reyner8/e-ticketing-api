<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case WaitingForUser = 'waiting_for_user';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingApproval => 'Pending Approval',
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::WaitingForUser => 'Waiting For User',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
            self::Converted => 'Converted',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function assignableStatuses(): array
    {
        return [
            self::Draft->value,
            self::PendingApproval->value,
            self::Assigned->value,
            self::InProgress->value,
            self::WaitingForUser->value,
        ];
    }
}
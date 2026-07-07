<?php

namespace App\Enums;

enum ErrorReportStatus: string
{
    case PendingApproval = 'pending_approval';
    case Assigned = 'assigned'; 
    case InProgress = 'in_progress'; 
    case Completed = 'completed'; 
    case Overdue = 'overdue'; 

    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Pending Approval',
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Overdue => 'Overdue',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function assignableStatuses(): array
    {
        return [
            self::PendingApproval->value,
            self::Assigned->value,
            self::InProgress->value,
            self::Overdue->value,
        ];
    }
}

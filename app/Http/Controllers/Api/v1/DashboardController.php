<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\DowntimeStatus;
use App\Enums\TicketStatus;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\DowntimeRecord;
use App\Models\Ticket;
use App\Services\TeamWorkloadSnapshotService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TeamWorkloadSnapshotService $workloadService
    ) {}

    public function stats(): JsonResponse
    {
        $openStatuses = [
            TicketStatus::Draft->value,
            TicketStatus::PendingApproval->value,
            TicketStatus::Assigned->value,
            TicketStatus::InProgress->value,
            TicketStatus::WaitingForUser->value,
        ];

        $snapshots = $this->workloadService->getLatestPerTeam();
        $downtimeMinutes = (int) DowntimeRecord::query()->sum('duration');

        return ApiResponse::success([
            'total_tickets' => Ticket::count(),
            'open_tickets' => Ticket::whereIn('status', $openStatuses)->count(),
            'resolved_today' => Ticket::whereDate('resolved_date', today())->count(),
            'overdue_tickets' => Ticket::where('sla_breached', true)->count(),
            'critical_tickets' => Ticket::where('priority', 'critical')->count(),
            'active_downtimes' => DowntimeRecord::where('status', DowntimeStatus::Ongoing->value)->count(),
            'downtime_hours' => round($downtimeMinutes / 60, 1),
            'average_resolution_time' => round((float) $snapshots->avg('average_resolution_time'), 2),
            'sla_compliance' => (int) round((float) $snapshots->avg('sla_compliance')),
            'user_satisfaction_score' => 4.2,
        ], 'Dashboard stats retrieved successfully.');
    }
}

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
        $monthStart = now()->startOfMonth();
        $downtimeMinutes = (int) DowntimeRecord::query()
            ->where('start_time', '>=', $monthStart)
            ->sum('duration');
        $periodHours = max(1, $monthStart->diffInHours(now()));
        $uptimePercent = round(max(0, min(100, 100 - (($downtimeMinutes / 60) / $periodHours) * 100)), 1);

        return ApiResponse::success([
            'total_tickets' => Ticket::count(),
            'open_tickets' => Ticket::whereIn('status', $openStatuses)->count(),
            'resolved_today' => Ticket::whereDate('resolved_date', today())->count(),
            'overdue_tickets' => Ticket::where('sla_breached', true)->count(),
            'critical_tickets' => Ticket::where('priority', 'critical')->count(),
            'active_downtimes' => DowntimeRecord::where('status', DowntimeStatus::Ongoing->value)->count(),
            'downtime_hours' => round($downtimeMinutes / 60, 1),
            'uptime_percent' => $uptimePercent,
            'average_resolution_time' => round((float) $snapshots->avg('average_resolution_time'), 2),
            'sla_compliance' => (int) round((float) $snapshots->avg('sla_compliance')),
            'user_satisfaction_score' => null,
            'status_breakdown' => [
                'in_progress' => Ticket::where('status', TicketStatus::InProgress->value)->count(),
                'resolved' => Ticket::whereIn('status', [
                    TicketStatus::Resolved->value,
                    TicketStatus::Closed->value,
                ])->count(),
            ],
        ], 'Dashboard stats retrieved successfully.');
    }
}

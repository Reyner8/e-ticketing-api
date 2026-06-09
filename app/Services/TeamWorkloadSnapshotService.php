<?php

namespace App\Services;

use App\Enums\AssignedTeam;
use App\Models\TeamWorkloadSnapshot;
use App\Models\Ticket;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TeamWorkloadSnapshotService
{
    public function generateForDate(string $date): Collection
    {
        $snapshots = collect();

        foreach (AssignedTeam::cases() as $team) {
            $snapshot = $this->generateForTeam($team, $date);
            $snapshots->push($snapshot);
        }

        return $snapshots;
    }

    public function generateForTeam(AssignedTeam $team, string $date): TeamWorkloadSnapshot
    {
        $metrics = $this->calculateMetrics($team, $date);

        return TeamWorkloadSnapshot::updateOrCreate([
            'team' => $team->value,
            'snapshot_date' => $date
        ], $metrics);
    }

    //* Query
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return TeamWorkloadSnapshot::query()
            ->when(
                isset($filters['team']),
                fn($q) => $q->byTeam($filters['team'])
            )
            ->when(
                isset($filters['from']) && isset($filters['to']),
                fn($q) => $q->inDateRange($filters['from'], $filters['to'])
            )
            ->when(
                isset($filters['date']),
                fn($q) => $q->byDate($filters['date'])
            )
            ->latest()
            ->paginate(min($perPage, 50));
    }

    public function getLatestPerTeam(): Collection
    {
        return collect(AssignedTeam::cases())->map(function (AssignedTeam $team) {
            return TeamWorkloadSnapshot::byTeam($team->value)
                ->latest()
                ->first();
        })->filter();
    }

    public function getTeamHistory(string $team, string $from, string $to, int $perPage = 30): LengthAwarePaginator
    {
        return TeamWorkloadSnapshot::byTeam($team)
            ->inDateRange($from, $to)
            ->latest()
            ->paginate(min($perPage, 90));
    }

    public function compareTeams(string $date): Collection
    {
        return TeamWorkloadSnapshot::byDate($date)
            ->orderByDesc('snapshot_date')
            ->get();
    }

    // Private
    private function calculateMetrics(AssignedTeam $team, string $date): array
    {
        $totalTickets = Ticket::where('assigned_team', $team->value)->count();

        $openTickets = Ticket::where('assigned_team', $team->value)
            ->whereNotIn('status', ['resolved', 'closed', 'converted'])
            ->count();

        $resolvedTickets = Ticket::where('assigned_team', $team->value)
            ->where('status', 'resolved')
            ->count();

        $overdueTickets = Ticket::where('assigned_team', $team->value)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $date . ' 23:59:59')
            ->whereNotIn('status', ['resolved', 'closed', 'converted'])
            ->count();

        $averageResponseTime = Ticket::where('assigned_team', $team->value)
            ->whereNotNull('assignment_date')
            ->whereNotNull('date_reported')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, date_reported, assignment_date)) / 60 as avg_hours')
            ->value('avg_hours');

        $averageResolutionTime = Ticket::where('assigned_team', $team->value)
            ->whereNotNull('resolved_date')
            ->whereNotNull('date_reported')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, date_reported, resolved_date)) / 60 as avg_hours')
            ->value('avg_hours');

        $slaCompliance = $this->calculateSlaCompliance($team);

        $workloadPercentage = $totalTickets > 0 ? round(($openTickets / $totalTickets) * 100, 2) : 0;

        return [
            'total_tickets' => $totalTickets,
            'open_tickets' => $openTickets,
            'resolved_tickets' => $resolvedTickets,
            'overdue_tickets' => $overdueTickets,
            'average_response_time' => $averageResponseTime,
            'average_resolution_time' => $averageResolutionTime,
            'sla_compliance' => $slaCompliance,
            'workload_percentage' => $workloadPercentage
        ];
    }

    private function calculateSlaCompliance(AssignedTeam $team): ?float
    {
        $totalWithDueDate = Ticket::where('assigned_team', $team->value)
            ->whereNotNull('due_date')
            ->whereIn('status', ['resolved', 'closed'])
            ->count();

        if ($totalWithDueDate === 0) {
            return null;
        }

        $resolvedBeforeDueDate = Ticket::where('assigned_team', $team->value)
            ->whereNotNull('due_date')
            ->whereIn('status', ['resolved', 'closed'])
            ->whereColumn('resolved_date', '<=', 'due_date')
            ->count();

        return round(($resolvedBeforeDueDate / $totalWithDueDate) * 100, 2);
    }
}

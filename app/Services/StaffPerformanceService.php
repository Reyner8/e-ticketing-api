<?php

namespace App\Services;

use App\Enums\AssignedTeam;
use App\Enums\ErrorReportStatus;
use App\Enums\FeatureRequestStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\ErrorReport;
use App\Models\FeatureRequest;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class StaffPerformanceService
{
    public const OPS_TEAMS = ['programmer', 'network'];

    public const SECTIONS = ['all', 'tickets', 'errors', 'features'];

    /** @var list<string> */
    private const TICKET_COMPLETED = [
        TicketStatus::Resolved->value,
        TicketStatus::Closed->value,
    ];

    /** @var list<string> */
    private const TICKET_TERMINAL = [
        TicketStatus::Resolved->value,
        TicketStatus::Closed->value,
        TicketStatus::Converted->value,
    ];

    /** @var list<string> */
    private const ERROR_COMPLETED = [
        ErrorReportStatus::Completed->value,
    ];

    /** @var list<string> */
    private const FR_COMPLETED = [
        FeatureRequestStatus::Completed->value,
        FeatureRequestStatus::PostImplementationReview->value,
    ];

    /** @var list<string> */
    private const FR_TERMINAL = [
        FeatureRequestStatus::Completed->value,
        FeatureRequestStatus::PostImplementationReview->value,
        FeatureRequestStatus::Rejected->value,
        FeatureRequestStatus::Cancelled->value,
    ];

    /**
     * @param  array{from?: string, to?: string, team?: string, user_id?: int|string, section?: string}  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters = []): array
    {
        $from = Carbon::parse($filters['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($filters['to'] ?? now()->toDateString())->endOfDay();
        $section = $this->normalizeSection($filters['section'] ?? 'all');
        $team = $this->resolveTeamFilter($filters['team'] ?? null);
        $userId = $this->resolveUserIdFilter($filters['user_id'] ?? null);

        $users = $this->staffUsers($team, $userId);
        $userIds = $users->pluck('id')->all();

        $tickets = $this->includeSection($section, 'tickets')
            ? $this->loadTickets($from, $to, $userIds)
            : collect();
        $errors = $this->includeSection($section, 'errors')
            ? $this->loadErrors($from, $to, $userIds)
            : collect();
        $features = $this->includeSection($section, 'features')
            ? $this->loadFeatures($from, $to, $userIds)
            : collect();

        $byUser = $users->map(function (User $user) use ($tickets, $errors, $features, $section) {
            $uid = (int) $user->id;
            $opsTeam = $this->normalizeOpsTeam(
                $user->team instanceof AssignedTeam ? $user->team->value : ($user->team ? (string) $user->team : null)
            );

            $row = [
                'user_id' => $uid,
                'name' => $user->name,
                'username' => $user->username,
                'team' => $opsTeam,
                'team_label' => $this->opsTeamLabel($opsTeam),
            ];

            if ($this->includeSection($section, 'tickets')) {
                $row['tickets'] = $this->metricsFor(
                    $tickets->where('assigned_to_id', $uid),
                    self::TICKET_COMPLETED,
                    self::TICKET_TERMINAL
                );
            }
            if ($this->includeSection($section, 'errors')) {
                $row['errors'] = $this->metricsFor(
                    $errors->where('assigned_to_id', $uid),
                    self::ERROR_COMPLETED,
                    self::ERROR_COMPLETED
                );
            }
            if ($this->includeSection($section, 'features')) {
                $row['features'] = $this->metricsFor(
                    $features->where('assigned_to_id', $uid),
                    self::FR_COMPLETED,
                    self::FR_TERMINAL
                );
            }

            return $row;
        })->values()->all();

        $summary = $this->emptySummary($section);
        $byTeamMap = [];

        foreach ($byUser as $row) {
            foreach (['tickets', 'errors', 'features'] as $key) {
                if (! isset($row[$key])) {
                    continue;
                }
                $summary[$key]['completed'] += $row[$key]['completed'];
                $summary[$key]['open'] += $row[$key]['open'];
                $summary[$key]['overdue'] += $row[$key]['overdue'];
            }

            $teamKey = $row['team'] ?? 'unassigned';
            if (! isset($byTeamMap[$teamKey])) {
                $byTeamMap[$teamKey] = [
                    'team' => $row['team'],
                    'team_label' => $row['team_label'],
                    ...$this->emptySummary($section),
                ];
            }
            foreach (['tickets', 'errors', 'features'] as $key) {
                if (! isset($row[$key])) {
                    continue;
                }
                $byTeamMap[$teamKey][$key]['completed'] += $row[$key]['completed'];
                $byTeamMap[$teamKey][$key]['open'] += $row[$key]['open'];
                $byTeamMap[$teamKey][$key]['overdue'] += $row[$key]['overdue'];
            }
        }

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'section' => $section,
            'team' => $team,
            'user_id' => $userId,
            'summary' => $summary,
            'by_team' => array_values($byTeamMap),
            'by_user' => $byUser,
        ];
    }

    /**
     * @param  array{from?: string, to?: string, team?: string, user_id?: int|string, section?: string}  $filters
     * @return array{0: string[], 1: array<int, array<int, mixed>>, 2: string}
     */
    public function exportRows(array $filters = []): array
    {
        $report = $this->build($filters);
        $headers = ['section', 'scope', 'key', 'label', 'resource', 'metric', 'value'];
        $rows = [];

        $rows[] = ['period', 'period', 'from', 'From', '-', 'date', $report['period']['from']];
        $rows[] = ['period', 'period', 'to', 'To', '-', 'date', $report['period']['to']];

        foreach ($report['summary'] as $resource => $metrics) {
            foreach ($metrics as $metric => $value) {
                $rows[] = ['summary', 'all', 'all', 'All staff', $resource, $metric, $value];
            }
        }

        foreach ($report['by_team'] as $teamRow) {
            $label = $teamRow['team_label'] ?? 'Unassigned';
            $key = $teamRow['team'] ?? 'unassigned';
            foreach (['tickets', 'errors', 'features'] as $resource) {
                if (! isset($teamRow[$resource])) {
                    continue;
                }
                foreach ($teamRow[$resource] as $metric => $value) {
                    $rows[] = ['by_team', 'team', $key, $label, $resource, $metric, $value];
                }
            }
        }

        foreach ($report['by_user'] as $userRow) {
            $label = $userRow['name'];
            $key = (string) $userRow['user_id'];
            foreach (['tickets', 'errors', 'features'] as $resource) {
                if (! isset($userRow[$resource])) {
                    continue;
                }
                foreach ($userRow[$resource] as $metric => $value) {
                    $rows[] = ['by_user', 'user', $key, $label, $resource, $metric, $value];
                }
            }
        }

        return [$headers, $rows, 'Staff Performance'];
    }

    private function normalizeSection(?string $section): string
    {
        $value = $section ?: 'all';

        return in_array($value, self::SECTIONS, true) ? $value : 'all';
    }

    private function includeSection(string $section, string $resource): bool
    {
        return $section === 'all' || $section === $resource;
    }

    private function resolveTeamFilter(?string $requested): ?string
    {
        return $this->normalizeOpsTeam($requested);
    }

    private function resolveUserIdFilter(int|string|null $requested): ?int
    {
        $user = Auth::user();
        if ($user && $user->role === UserRole::ItStaff) {
            return (int) $user->id;
        }

        if ($requested === null || $requested === '' || $requested === 'all') {
            return null;
        }

        return (int) $requested;
    }

    private function normalizeOpsTeam(?string $team): ?string
    {
        if ($team === null || $team === '' || $team === 'all') {
            return null;
        }

        if ($team === 'hardware') {
            return 'network';
        }

        return in_array($team, self::OPS_TEAMS, true) ? $team : null;
    }

    private function opsTeamLabel(?string $team): string
    {
        return match ($team) {
            'programmer' => 'Software Engineer',
            'network' => 'Network Engineer',
            default => 'Unassigned',
        };
    }

    /**
     * @return Collection<int, User>
     */
    private function staffUsers(?string $team, ?int $userId): Collection
    {
        $query = User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::ItStaff->value, UserRole::TeamLead->value])
            ->orderBy('name');

        if ($userId) {
            $query->where('id', $userId);
        }

        if ($team) {
            $teams = $team === 'network' ? ['network', 'hardware'] : [$team];
            $query->whereIn('team', $teams);
        }

        return $query->get(['id', 'name', 'username', 'team', 'role']);
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, Ticket>
     */
    private function loadTickets(Carbon $from, Carbon $to, array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return Ticket::query()
            ->whereIn('assigned_to_id', $userIds)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('date_reported', [$from, $to])
                    ->orWhereBetween('resolved_date', [$from, $to]);
            })
            ->get(['id', 'assigned_to_id', 'status', 'due_date', 'date_reported', 'resolved_date']);
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, ErrorReport>
     */
    private function loadErrors(Carbon $from, Carbon $to, array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return ErrorReport::query()
            ->whereIn('assigned_to_id', $userIds)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('date_reported', [$from, $to])
                    ->orWhereBetween('completion_date', [$from, $to])
                    ->orWhereBetween('created_at', [$from, $to]);
            })
            ->get(['id', 'assigned_to_id', 'status', 'due_date', 'date_reported', 'completion_date']);
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, FeatureRequest>
     */
    private function loadFeatures(Carbon $from, Carbon $to, array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        return FeatureRequest::query()
            ->whereIn('assigned_to_id', $userIds)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from, $to])
                    ->orWhere(function ($q2) use ($from, $to) {
                        $q2->whereIn('status', self::FR_COMPLETED)
                            ->whereBetween('updated_at', [$from, $to]);
                    });
            })
            ->get(['id', 'assigned_to_id', 'status', 'due_date', 'created_at', 'updated_at']);
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @param  list<string>  $completedStatuses
     * @param  list<string>  $terminalStatuses
     * @return array{completed: int, open: int, overdue: int}
     */
    private function metricsFor(Collection $items, array $completedStatuses, array $terminalStatuses): array
    {
        $now = now();
        $completed = 0;
        $open = 0;
        $overdue = 0;

        foreach ($items as $item) {
            $status = $item->status instanceof \BackedEnum
                ? $item->status->value
                : (string) $item->status;

            $isCompleted = in_array($status, $completedStatuses, true);
            $isOpen = ! in_array($status, $terminalStatuses, true);

            if ($isCompleted) {
                $completed++;
            }
            if ($isOpen) {
                $open++;
                if ($item->due_date && $item->due_date->lt($now)) {
                    $overdue++;
                }
            }
        }

        return [
            'completed' => $completed,
            'open' => $open,
            'overdue' => $overdue,
        ];
    }

    /**
     * @return array<string, array{completed: int, open: int, overdue: int}>
     */
    private function emptySummary(string $section): array
    {
        $empty = ['completed' => 0, 'open' => 0, 'overdue' => 0];
        $out = [];
        if ($this->includeSection($section, 'tickets')) {
            $out['tickets'] = $empty;
        }
        if ($this->includeSection($section, 'errors')) {
            $out['errors'] = $empty;
        }
        if ($this->includeSection($section, 'features')) {
            $out['features'] = $empty;
        }

        return $out;
    }
}

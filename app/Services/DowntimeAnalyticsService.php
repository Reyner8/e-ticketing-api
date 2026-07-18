<?php

namespace App\Services;

use App\Enums\DowntimeComponentRole;
use App\Enums\DowntimeStatus;
use App\Enums\DowntimeType;
use App\Models\DowntimeRecord;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DowntimeAnalyticsService
{
    public function summarize(array $filters = []): array
    {
        $from = isset($filters['from_date']) && $filters['from_date'] !== ''
            ? Carbon::parse($filters['from_date'])->startOfDay()
            : now()->startOfMonth();
        $to = isset($filters['to_date']) && $filters['to_date'] !== ''
            ? Carbon::parse($filters['to_date'])->endOfDay()
            : now()->endOfDay();

        if ($to->lessThan($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $periodMinutes = max(1, (int) $from->diffInMinutes($to));

        $records = $this->baseQuery($filters, $from, $to)
            ->with([
                'location:id,code,name',
                'sourceComponents:id,code,name,category',
                'affectedComponents:id,code,name,category',
            ])
            ->get();

        $totalIncidents = $records->count();
        $resolved = $records->where('status', DowntimeStatus::Resolved);
        $ongoing = $records->where('status', DowntimeStatus::Ongoing);
        $planned = $records->where('type', DowntimeType::Planned);
        $unplanned = $records->where('type', DowntimeType::Unplanned);

        $totalDowntimeMinutes = (int) $records->sum(function (DowntimeRecord $record) use ($to) {
            return $this->effectiveDurationMinutes($record, $to);
        });

        $averageDowntimeMinutes = $totalIncidents > 0
            ? (int) round($totalDowntimeMinutes / $totalIncidents)
            : 0;

        $totalCost = (float) $records->sum(fn (DowntimeRecord $r) => (float) ($r->estimated_cost ?? 0));
        $totalAffectedUsers = (int) $records->sum(fn (DowntimeRecord $r) => (int) ($r->affected_users ?? 0));

        $impactBreakdown = $records
            ->groupBy(fn (DowntimeRecord $r) => $r->impact?->value ?? (string) $r->impact)
            ->map(fn (Collection $group, string $impact) => [
                'impact' => $impact,
                'count' => $group->count(),
                'total_minutes' => (int) $group->sum(fn (DowntimeRecord $r) => $this->effectiveDurationMinutes($r, $to)),
            ])
            ->values()
            ->all();

        $sourceStats = $this->componentStats($records, DowntimeComponentRole::Source, $periodMinutes, $to);
        $affectedStats = $this->componentStats($records, DowntimeComponentRole::Affected, $periodMinutes, $to);
        $categoryStats = $this->categoryStats($records, $periodMinutes, $to);

        $locationStats = $records
            ->groupBy(fn (DowntimeRecord $r) => $r->location_id ?: 0)
            ->map(function (Collection $group) use ($to) {
                /** @var DowntimeRecord $first */
                $first = $group->first();
                return [
                    'location_id' => $first->location_id,
                    'location_name' => $first->location?->name ?? 'Unspecified',
                    'incident_count' => $group->count(),
                    'total_minutes' => (int) $group->sum(fn (DowntimeRecord $r) => $this->effectiveDurationMinutes($r, $to)),
                ];
            })
            ->sortByDesc('incident_count')
            ->values()
            ->all();

        return [
            'period' => [
                'from' => $from->format('Y-m-d H:i:s'),
                'to' => $to->format('Y-m-d H:i:s'),
                'period_minutes' => $periodMinutes,
            ],
            'summary' => [
                'incident_count' => $totalIncidents,
                'ongoing_count' => $ongoing->count(),
                'resolved_count' => $resolved->count(),
                'planned_count' => $planned->count(),
                'unplanned_count' => $unplanned->count(),
                'total_downtime_minutes' => $totalDowntimeMinutes,
                'average_downtime_minutes' => $averageDowntimeMinutes,
                'total_estimated_cost' => round($totalCost, 2),
                'total_affected_users' => $totalAffectedUsers,
            ],
            'impact_breakdown' => $impactBreakdown,
            'most_frequent_sources' => array_slice($sourceStats, 0, 10),
            'most_affected_components' => array_slice($affectedStats, 0, 10),
            'location_frequency' => $locationStats,
            'component_uptime' => $sourceStats,
            'category_uptime' => $categoryStats,
        ];
    }

    private function baseQuery(array $filters, Carbon $from, Carbon $to): Builder
    {
        return DowntimeRecord::query()
            ->where('start_time', '<=', $to)
            ->where(function ($q) use ($from) {
                $q->whereNull('end_time')
                    ->orWhere('end_time', '>=', $from);
            })
            ->when(isset($filters['location_id']) && $filters['location_id'] !== '', fn ($q) => $q->where('location_id', $filters['location_id']))
            ->when(isset($filters['type']) && $filters['type'] !== '', fn ($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['status']) && $filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['impact']) && $filters['impact'] !== '', fn ($q) => $q->where('impact', $filters['impact']))
            ->when(isset($filters['component_id']) && $filters['component_id'] !== '', function ($q) use ($filters) {
                $q->whereHas('recordComponents', fn ($inner) => $inner->where('component_id', $filters['component_id']));
            })
            ->when(isset($filters['category']) && $filters['category'] !== '', function ($q) use ($filters) {
                $q->whereHas('recordComponents.component', fn ($inner) => $inner->where('category', $filters['category']));
            });
    }

    private function effectiveDurationMinutes(DowntimeRecord $record, Carbon $periodEnd): int
    {
        if (! is_null($record->duration)) {
            return (int) $record->duration;
        }

        if ($record->start_time) {
            $end = $record->end_time ?? $periodEnd;

            return max(0, (int) $record->start_time->diffInMinutes($end));
        }

        return 0;
    }

    private function componentStats(Collection $records, DowntimeComponentRole $role, int $periodMinutes, Carbon $to): array
    {
        $buckets = [];

        foreach ($records as $record) {
            $components = $role === DowntimeComponentRole::Source
                ? $record->sourceComponents
                : $record->affectedComponents;

            $minutes = $this->effectiveDurationMinutes($record, $to);

            foreach ($components as $component) {
                $id = $component->id;
                if (! isset($buckets[$id])) {
                    $buckets[$id] = [
                        'component_id' => $component->id,
                        'code' => $component->code,
                        'name' => $component->name,
                        'category' => $component->category?->value ?? $component->category,
                        'incident_count' => 0,
                        'total_minutes' => 0,
                    ];
                }
                $buckets[$id]['incident_count']++;
                $buckets[$id]['total_minutes'] += $minutes;
            }
        }

        return collect($buckets)
            ->map(function (array $row) use ($periodMinutes) {
                $downtime = min($periodMinutes, (int) $row['total_minutes']);
                $uptime = max(0, $periodMinutes - $downtime);

                return [
                    ...$row,
                    'uptime_percent' => round(($uptime / $periodMinutes) * 100, 2),
                    'downtime_percent' => round(($downtime / $periodMinutes) * 100, 2),
                ];
            })
            ->sortByDesc('incident_count')
            ->values()
            ->all();
    }

    private function categoryStats(Collection $records, int $periodMinutes, Carbon $to): array
    {
        $buckets = [];

        foreach ($records as $record) {
            $minutes = $this->effectiveDurationMinutes($record, $to);
            foreach ($record->sourceComponents as $component) {
                $category = $component->category?->value ?? (string) $component->category;
                if (! isset($buckets[$category])) {
                    $buckets[$category] = [
                        'category' => $category,
                        'incident_count' => 0,
                        'total_minutes' => 0,
                    ];
                }
                $buckets[$category]['incident_count']++;
                $buckets[$category]['total_minutes'] += $minutes;
            }
        }

        return collect($buckets)
            ->map(function (array $row) use ($periodMinutes) {
                $downtime = min($periodMinutes, (int) $row['total_minutes']);
                $uptime = max(0, $periodMinutes - $downtime);

                return [
                    ...$row,
                    'uptime_percent' => round(($uptime / $periodMinutes) * 100, 2),
                    'downtime_percent' => round(($downtime / $periodMinutes) * 100, 2),
                ];
            })
            ->sortByDesc('total_minutes')
            ->values()
            ->all();
    }
}

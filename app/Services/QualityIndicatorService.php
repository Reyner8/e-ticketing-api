<?php

namespace App\Services;

use App\Enums\FeatureRequestStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\FeatureRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Quality indicator report: cumulative Feature Request volume vs. completion,
 * bucketed by semester (H1: Jan-Jun, H2: Jul-Dec), filtered by target application
 * (e.g. RME). Counters never reset — each semester shows the running total since
 * the first matching Feature Request was created, matching hospital "indikator mutu"
 * style reporting (nilai kumulatif per semester).
 */
class QualityIndicatorService
{
    private const DEFAULT_APPLICATION = 'rme';

    /** @var list<string> */
    private const FR_COMPLETED = [
        FeatureRequestStatus::Completed->value,
        FeatureRequestStatus::PostImplementationReview->value,
    ];

    /**
     * @param  array{application?: string, user_id?: int|string}  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters = []): array
    {
        $applicationCode = $this->normalizeApplication($filters['application'] ?? null);
        $userId = $this->resolveUserIdFilter($filters['user_id'] ?? null);

        $query = FeatureRequest::query()->where('target_application', $applicationCode);
        if ($userId) {
            $query->where('assigned_to_id', $userId);
        }

        $items = $query
            ->with(['statusHistories' => function ($q) {
                $q->whereIn('new_status', self::FR_COMPLETED)->orderBy('effective_at');
            }])
            ->get(['id', 'assigned_to_id', 'status', 'created_at', 'updated_at']);

        $userName = null;
        if ($userId) {
            $userName = User::query()->whereKey($userId)->value('name');
        }

        if ($items->isEmpty()) {
            return [
                'application' => $this->applicationOption($applicationCode),
                'user_id' => $userId,
                'user_name' => $userName,
                'generated_at' => now()->toIso8601String(),
                'semesters' => [],
            ];
        }

        // Resolve the effective "reached completed" timestamp per item (first time it
        // transitioned into a completed status, falling back to updated_at if the
        // status history is missing, e.g. for older seeded data).
        $completedAt = $items->mapWithKeys(function (FeatureRequest $fr) {
            $status = $fr->status instanceof \BackedEnum ? $fr->status->value : (string) $fr->status;
            if (! in_array($status, self::FR_COMPLETED, true)) {
                return [$fr->id => null];
            }

            $firstTransition = $fr->statusHistories->first();

            return [$fr->id => $firstTransition?->effective_at ?? $fr->updated_at];
        });

        $earliest = $items->min('created_at');
        $semesters = $this->semesterRange($earliest, now());

        $rows = [];
        foreach ($semesters as $semester) {
            $end = $semester['end'];

            $total = $items->filter(fn (FeatureRequest $fr) => $fr->created_at->lte($end))->count();
            $completed = $completedAt->filter(fn ($at) => $at !== null && $at->lte($end))->count();

            $rows[] = [
                'year' => $semester['year'],
                'semester' => $semester['semester'],
                'label' => sprintf('%d - Semester %d', $semester['year'], $semester['semester']),
                'period' => [
                    'from' => $semester['start']->toDateString(),
                    'to' => $semester['end']->toDateString(),
                ],
                'total' => $total,
                'completed' => $completed,
                'completion_rate' => $total > 0 ? round($completed / $total * 100, 1) : 0.0,
            ];
        }

        return [
            'application' => $this->applicationOption($applicationCode),
            'user_id' => $userId,
            'user_name' => $userName,
            'generated_at' => now()->toIso8601String(),
            'semesters' => $rows,
        ];
    }

    /**
     * @param  array{application?: string, user_id?: int|string}  $filters
     * @return array{0: string[], 1: array<int, array<int, mixed>>, 2: string}
     */
    public function exportRows(array $filters = []): array
    {
        $report = $this->build($filters);
        $headers = ['application', 'user', 'year', 'semester', 'period_from', 'period_to', 'total', 'completed', 'completion_rate'];
        $rows = [];

        $applicationLabel = $report['application']['label'] ?? $report['application']['value'] ?? '';
        $userLabel = $report['user_name'] ?? 'All staff';

        foreach ($report['semesters'] as $row) {
            $rows[] = [
                $applicationLabel,
                $userLabel,
                $row['year'],
                $row['semester'],
                $row['period']['from'],
                $row['period']['to'],
                $row['total'],
                $row['completed'],
                $row['completion_rate'],
            ];
        }

        return [$headers, $rows, 'Quality Indicator - Feature Requests'];
    }

    private function normalizeApplication(?string $code): string
    {
        $code = trim((string) $code);

        return $code !== '' ? $code : self::DEFAULT_APPLICATION;
    }

    /**
     * @return array{value: string, label: string}
     */
    private function applicationOption(string $code): array
    {
        return Application::toOption($code) ?? ['value' => $code, 'label' => $code];
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

    /**
     * Build the list of semesters from the first matching item through "now", inclusive.
     * Counters are cumulative and never reset across years.
     *
     * @return list<array{year: int, semester: int, start: Carbon, end: Carbon}>
     */
    private function semesterRange(Carbon $from, Carbon $to): array
    {
        $cursorYear = $from->year;
        $cursorSemester = $from->month <= 6 ? 1 : 2;
        $endYear = $to->year;
        $endSemester = $to->month <= 6 ? 1 : 2;

        $out = [];
        while ($cursorYear < $endYear || ($cursorYear === $endYear && $cursorSemester <= $endSemester)) {
            $start = $cursorSemester === 1
                ? Carbon::create($cursorYear, 1, 1)->startOfDay()
                : Carbon::create($cursorYear, 7, 1)->startOfDay();
            $end = $cursorSemester === 1
                ? Carbon::create($cursorYear, 6, 30)->endOfDay()
                : Carbon::create($cursorYear, 12, 31)->endOfDay();

            $out[] = [
                'year' => $cursorYear,
                'semester' => $cursorSemester,
                'start' => $start,
                'end' => $end,
            ];

            if ($cursorSemester === 1) {
                $cursorSemester = 2;
            } else {
                $cursorSemester = 1;
                $cursorYear++;
            }
        }

        return $out;
    }
}

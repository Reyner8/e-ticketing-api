<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\DowntimeRecord;
use App\Models\ErrorReport;
use App\Models\FeatureRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private const DATASETS = ['tickets', 'errors', 'features', 'downtimes', 'users'];

    public function export(Request $request, string $dataset): Response
    {
        abort_unless(in_array($dataset, self::DATASETS, true), 404, 'Unknown export dataset.');

        if ($dataset === 'users' && $request->user()?->role !== 'admin') {
            abort(403, 'Only administrators can export users.');
        }

        $format = $request->query('format', 'csv');
        abort_unless(in_array($format, ['csv', 'excel', 'pdf'], true), 422, 'Invalid export format.');

        $filters = $request->only([
            'from_date',
            'to_date',
            'location_id',
            'component_id',
            'category',
            'type',
            'status',
            'impact',
        ]);

        return match ($format) {
            'pdf' => $this->exportPdf($dataset, $filters),
            'excel' => $this->exportExcel($dataset, $filters),
            default => $this->exportCsv($dataset, $filters),
        };
    }

    private function exportCsv(string $dataset, array $filters = []): StreamedResponse
    {
        $filename = sprintf('%s-export-%s.csv', $dataset, now()->format('Y-m-d_His'));

        return response()->streamDownload(function () use ($dataset, $filters) {
            $handle = fopen('php://output', 'w');
            $this->writeDataset($handle, $dataset, $filters);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportExcel(string $dataset, array $filters = []): StreamedResponse
    {
        $filename = sprintf('%s-export-%s.xls', $dataset, now()->format('Y-m-d_His'));

        return response()->streamDownload(function () use ($dataset, $filters) {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');
            $this->writeDataset($handle, $dataset, $filters);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function exportPdf(string $dataset, array $filters = []): Response
    {
        [$headers, $rows, $title] = $this->collectDataset($dataset, $filters);

        $html = View::make('exports.dataset', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ])->render();

        $filename = sprintf('%s-export-%s.pdf', $dataset, now()->format('Y-m-d_His'));

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}.html\"",
        ]);
    }

    private function writeDataset($handle, string $dataset, array $filters = []): void
    {
        [$headers, $rows] = $this->collectDataset($dataset, $filters);
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
    }

    /**
     * @return array{0: string[], 1: array<int, array<int, mixed>>, 2?: string}
     */
    private function collectDataset(string $dataset, array $filters = []): array
    {
        return match ($dataset) {
            'tickets' => [
                ['id', 'title', 'category', 'priority', 'status', 'reporter_id', 'assigned_to_id', 'assigned_team', 'date_reported', 'due_date', 'sla_breached'],
                Ticket::query()->orderByDesc('date_reported')->limit(5000)->get()->map(fn (Ticket $ticket) => [
                    $ticket->id,
                    $ticket->title,
                    $ticket->category?->value ?? $ticket->category,
                    $ticket->priority?->value ?? $ticket->priority,
                    $ticket->status?->value ?? $ticket->status,
                    $ticket->reporter_id,
                    $ticket->assigned_to_id,
                    $ticket->assigned_team?->value ?? $ticket->assigned_team,
                    $ticket->date_reported?->format('Y-m-d H:i:s'),
                    $ticket->due_date?->format('Y-m-d H:i:s'),
                    $ticket->sla_breached ? 'yes' : 'no',
                ])->all(),
                'Tickets Export',
            ],
            'errors' => [
                ['id', 'title', 'category', 'priority', 'status', 'assigned_to_id', 'assigned_team', 'due_date', 'sla_breached'],
                ErrorReport::query()->orderByDesc('created_at')->limit(5000)->get()->map(fn (ErrorReport $report) => [
                    $report->id,
                    $report->title,
                    $report->category?->value ?? $report->category,
                    $report->priority?->value ?? $report->priority,
                    $report->status?->value ?? $report->status,
                    $report->assigned_to_id,
                    $report->assigned_team?->value ?? $report->assigned_team,
                    $report->due_date?->format('Y-m-d H:i:s'),
                    $report->sla_breached ? 'yes' : 'no',
                ])->all(),
                'Error Reports Export',
            ],
            'features' => [
                ['id', 'title', 'request_type', 'priority', 'status', 'assigned_to_id', 'assigned_team', 'due_date', 'progress'],
                FeatureRequest::query()->orderByDesc('created_at')->limit(5000)->get()->map(fn (FeatureRequest $feature) => [
                    $feature->id,
                    $feature->title,
                    $feature->request_type?->value ?? $feature->request_type,
                    $feature->priority?->value ?? $feature->priority,
                    $feature->status?->value ?? $feature->status,
                    $feature->assigned_to_id,
                    $feature->assigned_team?->value ?? $feature->assigned_team,
                    $feature->due_date?->format('Y-m-d H:i:s'),
                    $feature->progress,
                ])->all(),
                'Feature Requests Export',
            ],
            'downtimes' => [
                [
                    'id',
                    'title',
                    'type',
                    'status',
                    'impact',
                    'location',
                    'direct_sources',
                    'affected_components',
                    'reason',
                    'start_time',
                    'end_time',
                    'duration_minutes',
                    'root_cause',
                    'preventive_measures',
                    'affected_users',
                    'estimated_cost',
                ],
                DowntimeRecord::query()
                    ->with([
                        'location:id,name',
                        'sourceComponents:id,name',
                        'affectedComponents:id,name',
                    ])
                    ->when(isset($filters['from_date']) && $filters['from_date'] !== '', function ($q) use ($filters) {
                        $q->where('start_time', '>=', $filters['from_date']);
                    })
                    ->when(isset($filters['to_date']) && $filters['to_date'] !== '', function ($q) use ($filters) {
                        $q->where('start_time', '<=', $filters['to_date'].' 23:59:59');
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
                    })
                    ->orderByDesc('start_time')
                    ->limit(5000)
                    ->get()
                    ->map(fn (DowntimeRecord $record) => [
                        $record->id,
                        $record->title,
                        $record->type?->value ?? $record->type,
                        $record->status?->value ?? $record->status,
                        $record->impact?->value ?? $record->impact,
                        $record->location?->name,
                        $record->sourceComponents->pluck('name')->implode('; '),
                        $record->affectedComponents->pluck('name')->implode('; '),
                        $record->reason,
                        $record->start_time?->format('Y-m-d H:i:s'),
                        $record->end_time?->format('Y-m-d H:i:s'),
                        $record->duration,
                        $record->root_cause,
                        $record->preventive_measures,
                        $record->affected_users,
                        $record->estimated_cost,
                    ])->all(),
                'Downtime Records Export',
            ],
            'users' => [
                ['id', 'name', 'username', 'email', 'role', 'team', 'is_active', 'created_at'],
                User::query()->orderBy('name')->limit(5000)->get()->map(fn (User $user) => [
                    $user->id,
                    $user->name,
                    $user->username,
                    $user->email,
                    $user->role?->value ?? $user->role,
                    $user->team?->value ?? $user->team,
                    $user->is_active ? 'yes' : 'no',
                    $user->created_at?->format('Y-m-d H:i:s'),
                ])->all(),
                'Users Export',
            ],
        };
    }
}

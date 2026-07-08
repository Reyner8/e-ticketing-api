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

        return match ($format) {
            'pdf' => $this->exportPdf($dataset),
            'excel' => $this->exportExcel($dataset),
            default => $this->exportCsv($dataset),
        };
    }

    private function exportCsv(string $dataset): StreamedResponse
    {
        $filename = sprintf('%s-export-%s.csv', $dataset, now()->format('Y-m-d_His'));

        return response()->streamDownload(function () use ($dataset) {
            $handle = fopen('php://output', 'w');
            $this->writeDataset($handle, $dataset);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportExcel(string $dataset): StreamedResponse
    {
        $filename = sprintf('%s-export-%s.xls', $dataset, now()->format('Y-m-d_His'));

        return response()->streamDownload(function () use ($dataset) {
            echo "\xEF\xBB\xBF";
            $handle = fopen('php://output', 'w');
            $this->writeDataset($handle, $dataset);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function exportPdf(string $dataset): Response
    {
        [$headers, $rows, $title] = $this->collectDataset($dataset);

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

    private function writeDataset($handle, string $dataset): void
    {
        [$headers, $rows] = $this->collectDataset($dataset);
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
    }

    /**
     * @return array{0: string[], 1: array<int, array<int, mixed>>, 2?: string}
     */
    private function collectDataset(string $dataset): array
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
                ['id', 'title', 'type', 'status', 'impact', 'reason', 'start_time', 'end_time', 'duration_minutes'],
                DowntimeRecord::query()->orderByDesc('start_time')->limit(5000)->get()->map(fn (DowntimeRecord $record) => [
                    $record->id,
                    $record->title,
                    $record->type?->value ?? $record->type,
                    $record->status?->value ?? $record->status,
                    $record->impact?->value ?? $record->impact,
                    $record->reason,
                    $record->start_time?->format('Y-m-d H:i:s'),
                    $record->end_time?->format('Y-m-d H:i:s'),
                    $record->duration,
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

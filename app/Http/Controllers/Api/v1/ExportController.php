<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\DowntimeRecord;
use App\Models\ErrorReport;
use App\Models\FeatureRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private const DATASETS = ['tickets', 'errors', 'features', 'downtimes', 'users'];

    public function csv(Request $request, string $dataset): StreamedResponse
    {
        abort_unless(in_array($dataset, self::DATASETS, true), 404, 'Unknown export dataset.');

        if ($dataset === 'users' && $request->user()?->role !== 'admin') {
            abort(403, 'Only administrators can export users.');
        }

        $filename = sprintf('%s-export-%s.csv', $dataset, now()->format('Y-m-d_His'));

        return response()->streamDownload(function () use ($dataset) {
            $handle = fopen('php://output', 'w');

            match ($dataset) {
                'tickets' => $this->exportTickets($handle),
                'errors' => $this->exportErrors($handle),
                'features' => $this->exportFeatures($handle),
                'downtimes' => $this->exportDowntimes($handle),
                'users' => $this->exportUsers($handle),
            };

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportTickets($handle): void
    {
        fputcsv($handle, ['id', 'title', 'category', 'priority', 'status', 'reporter_id', 'assigned_to_id', 'assigned_team', 'date_reported', 'due_date', 'sla_breached']);

        Ticket::query()
            ->orderByDesc('date_reported')
            ->limit(5000)
            ->cursor()
            ->each(function (Ticket $ticket) use ($handle) {
                fputcsv($handle, [
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
                ]);
            });
    }

    private function exportErrors($handle): void
    {
        fputcsv($handle, ['id', 'title', 'category', 'priority', 'status', 'assigned_to_id', 'assigned_team', 'due_date', 'sla_breached']);

        ErrorReport::query()
            ->orderByDesc('created_at')
            ->limit(5000)
            ->cursor()
            ->each(function (ErrorReport $report) use ($handle) {
                fputcsv($handle, [
                    $report->id,
                    $report->title,
                    $report->category?->value ?? $report->category,
                    $report->priority?->value ?? $report->priority,
                    $report->status?->value ?? $report->status,
                    $report->assigned_to_id,
                    $report->assigned_team?->value ?? $report->assigned_team,
                    $report->due_date?->format('Y-m-d H:i:s'),
                    $report->sla_breached ? 'yes' : 'no',
                ]);
            });
    }

    private function exportFeatures($handle): void
    {
        fputcsv($handle, ['id', 'title', 'request_type', 'priority', 'status', 'assigned_to_id', 'assigned_team', 'due_date', 'progress']);

        FeatureRequest::query()
            ->orderByDesc('created_at')
            ->limit(5000)
            ->cursor()
            ->each(function (FeatureRequest $feature) use ($handle) {
                fputcsv($handle, [
                    $feature->id,
                    $feature->title,
                    $feature->request_type?->value ?? $feature->request_type,
                    $feature->priority?->value ?? $feature->priority,
                    $feature->status?->value ?? $feature->status,
                    $feature->assigned_to_id,
                    $feature->assigned_team?->value ?? $feature->assigned_team,
                    $feature->due_date?->format('Y-m-d H:i:s'),
                    $feature->progress,
                ]);
            });
    }

    private function exportDowntimes($handle): void
    {
        fputcsv($handle, ['id', 'title', 'type', 'status', 'impact', 'reason', 'start_time', 'end_time', 'duration_minutes']);

        DowntimeRecord::query()
            ->orderByDesc('start_time')
            ->limit(5000)
            ->cursor()
            ->each(function (DowntimeRecord $record) use ($handle) {
                fputcsv($handle, [
                    $record->id,
                    $record->title,
                    $record->type?->value ?? $record->type,
                    $record->status?->value ?? $record->status,
                    $record->impact?->value ?? $record->impact,
                    $record->reason,
                    $record->start_time?->format('Y-m-d H:i:s'),
                    $record->end_time?->format('Y-m-d H:i:s'),
                    is_object($record->duration) ? ($record->duration->minutes ?? null) : $record->duration,
                ]);
            });
    }

    private function exportUsers($handle): void
    {
        fputcsv($handle, ['id', 'name', 'username', 'email', 'role', 'team', 'is_active', 'created_at']);

        User::query()
            ->orderBy('name')
            ->limit(5000)
            ->cursor()
            ->each(function (User $user) use ($handle) {
                fputcsv($handle, [
                    $user->id,
                    $user->name,
                    $user->username,
                    $user->email,
                    $user->role?->value ?? $user->role,
                    $user->team?->value ?? $user->team,
                    $user->is_active ? 'yes' : 'no',
                    $user->created_at?->format('Y-m-d H:i:s'),
                ]);
            });
    }
}

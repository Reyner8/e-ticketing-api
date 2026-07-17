<?php

namespace App\Services;

use App\Enums\ErrorReportStatus;
use App\Enums\UserRole;
use App\Models\ErrorReport;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ErrorReportService
{
    public function store(array $data): ErrorReport
    {
        $errorReport = ErrorReport::create([
            ...$data,
            'id' => $this->generateErrorReportId(),
            'reporter_id' => Auth::id(),
            'status' => ErrorReportStatus::PendingApproval->value,
            'date_reported' => now(),
            'is_direct_input' => true,
            'source_ticket_id' => null,
            'due_date' => null
        ]);

        return $errorReport->load(['reporter', 'assignedUser']);
    }

    public function update(ErrorReport $error, array $data): ErrorReport
    {
        if ($error->status === ErrorReportStatus::Completed->value) {
            throw ValidationException::withMessages([
                'status' => ['Completed error report cannot be edited.']
            ]);
        }

        $error->update($data);

        return $error->load(['reporter', 'assignedUser']);
    }

    public function delete(ErrorReport $error): void
    {
        if ($error->status === ErrorReportStatus::Completed->value) {
            throw ValidationException::withMessages([
                'status' => ['Completed error report cannot be deleted.']
            ]);
        }

        $error->delete();
    }

    //* Query
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $user = Auth::user();

        return ErrorReport::query()
            ->with(['reporter:id,username,name', 'assignedUser:id,username,name'])
            ->when(
                $user && $user->role === UserRole::Reporter,
                fn($q) => $q->where('reporter_id', $user->id)
            )
            ->when(
                isset($filters['status']),
                fn($q) => $q->byStatus('status', $filters['status'])
            )
            ->when(
                isset($filters['priority']),
                fn($q) => $q->byPriority('priority', $filters['priority'])
            )
            ->when(
                isset($filters['category']),
                fn($q) => $q->byCategory('category', $filters['category'])
            )
            ->when(
                isset($filters['assigned_team']),
                fn($q) => $q->where('assigned_team', $filters['assigned_team'])
            )
            ->when(
                isset($filters['reporter_id']),
                fn($q) => $q->where('reporter_id', $filters['reporter_id'])
            )
            ->when(
                isset($filters['sla_breached']),
                fn($q) => $q->slaBreached()
            )
            ->when(
                isset($filters['overdue']),
                fn($q) => $q->overdue()
            )
            ->when(
                isset($filters['is_direct_input']),
                function ($q) use ($filters) {
                    $isDirect = filter_var($filters['is_direct_input'], FILTER_VALIDATE_BOOLEAN);
                    return $isDirect ? $q->directInput() : $q->fromTicket();
                }
            )
            ->when(
                isset($filters['search']),
                fn($q) => $q->where('title', 'like', '%' . $filters['search'] . '%')
            )
            ->latest('date_reported')
            ->paginate(min($perPage, 50));
    }

    // Private
    private function generateErrorReportId(): string
    {
        return DB::transaction(function () {
            $prefix = 'ERR';
            $year = now()->format('Y');

            $lastErrorReport = ErrorReport::where('id', 'like', "{$prefix}-{$year}-%")
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $lastNumber = $lastErrorReport ? (int) substr($lastErrorReport->id, -3) : 0;
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

            return "{$prefix}-{$year}-{$newNumber}";
        });
    }
}

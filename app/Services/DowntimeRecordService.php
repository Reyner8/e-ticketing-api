<?php

namespace App\Services;

use App\Enums\DowntimeStatus;
use App\Models\DowntimeRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DowntimeRecordService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function store(array $data): DowntimeRecord
    {
        $status = isset($data['end_time'])
            ? DowntimeStatus::Resolved->value
            : DowntimeStatus::Ongoing->value;

        $record = DowntimeRecord::create([
            ...$data,
            'reported_by' => Auth::id(),
            'status' => $status,
        ]);

        if (! is_null($record->end_time)) {
            $record->update(['duration' => $record->calculateDuration()]);
        }

        $this->notifyStaffAboutDowntime($record);

        return $record->load('reporter');
    }

    public function update(DowntimeRecord $record, array $data): DowntimeRecord
    {
        if ($record->isResolved()) {
            throw ValidationException::withMessages([
                'status' => ['Downtime record is already resolved']
            ]);
        }

        $record->update($data);

        if (isset($data['end_time']) && ! is_null($record->fresh()->end_time)) {
            $record->update(['duration' => $record->calculateDuration()]);
        }

        return $record->load('reporter');
    }

    public function resolve(DowntimeRecord $record, array $data): DowntimeRecord
    {
        if ($record->isResolved()) {
            throw ValidationException::withMessages([
                'status' => ['Downtime record is already resolved']
            ]);
        }

        $endtime = Carbon::parse($data['end_time']);
        if ($endtime->isBefore($record->start_time)) {
            throw ValidationException::withMessages([
                'end_time' => ['End time cannot be before start time.']
            ]);
        }

        $record->update([
            'status' => DowntimeStatus::Resolved->value,
            'end_time' => $data['end_time'],
            'root_cause' => $data['root_cause'],
            'preventive_measures' => $data['preventive_measures'],
            'affected_users' => $data['affected_users'],
            'estimated_cost' => $data['estimated_cost'],
        ]);

        $record->update(['duration' => $record->calculateDuration()]);

        return $record->load('reporter');
    }

    public function delete(DowntimeRecord $record): void
    {
        if ($record->isResolved()) {
            throw ValidationException::withMessages([
                'status' => ['Resolved downtime record cannot be deleted.']
            ]);
        }

        $record->delete();
    }

    //* Query
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return DowntimeRecord::query()
            ->with('reporter:id,name,username')
            ->when(isset($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['impact']), fn($q) => $q->where('impact', $filters['impact']))
            ->when(isset($filters['from_date']), fn($q) => $q->where('from_date', $filters['from_date']))
            ->when(isset($filters['to_date']), fn($q) => $q->where('to_date', $filters['to_date']))
            ->latest()  
            ->paginate(min($perPage, 50));
    }

    // Private
    private function notifyStaffAboutDowntime(DowntimeRecord $record): void
    {
        User::where('role', 'it_staff')
        ->where('is_active', true)
        ->where('id', '!=', Auth::id())
        ->get()
        ->each(function (User $user) use ($record) {
            $this->notificationService->notifyDowntimeAlert(
                userId: $user->id,
                downtime: $record
            );
        });
    }
}

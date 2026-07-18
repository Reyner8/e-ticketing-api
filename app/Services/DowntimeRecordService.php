<?php

namespace App\Services;

use App\Enums\DowntimeComponentRole;
use App\Enums\DowntimeStatus;
use App\Models\DowntimeComponent;
use App\Models\DowntimeLocation;
use App\Models\DowntimeRecord;
use App\Models\DowntimeRecordComponent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DowntimeRecordService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function store(array $data): DowntimeRecord
    {
        return DB::transaction(function () use ($data) {
            $startTime = Carbon::parse($data['start_time']);
            $endTime = isset($data['end_time']) ? Carbon::parse($data['end_time']) : null;

            if ($endTime && ! $endTime->greaterThan($startTime)) {
                throw ValidationException::withMessages([
                    'end_time' => ['End time must be after start time.'],
                ]);
            }

            $sourceIds = $this->normalizeComponentIds($data['source_component_ids'] ?? []);
            $affectedIds = $this->normalizeComponentIds($data['affected_component_ids'] ?? []);
            $locationIds = $this->normalizeIds($data['location_ids'] ?? []);
            $this->assertActiveComponents([...$sourceIds, ...$affectedIds]);
            $this->assertActiveLocations($locationIds);
            $this->assertNoRoleOverlap($sourceIds, $affectedIds);

            $status = $endTime
                ? DowntimeStatus::Resolved->value
                : DowntimeStatus::Ongoing->value;

            $record = DowntimeRecord::create([
                'title' => $data['title'],
                'type' => $data['type'],
                'reason' => $data['reason'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'impact' => $data['impact'],
                'reported_by' => Auth::id(),
                'description' => $data['description'] ?? null,
                'status' => $status,
                'root_cause' => $data['root_cause'] ?? null,
                'preventive_measures' => $data['preventive_measures'] ?? null,
                'affected_users' => $data['affected_users'] ?? null,
                'estimated_cost' => $data['estimated_cost'] ?? null,
                'duration' => $endTime ? (int) $startTime->diffInMinutes($endTime) : null,
            ]);

            $this->syncRecordComponents($record, $sourceIds, $affectedIds);
            $record->locations()->sync($locationIds);
            $this->notifyStaffAboutDowntime($record);

            return $this->loadRecord($record);
        });
    }

    public function update(DowntimeRecord $record, array $data): DowntimeRecord
    {
        return DB::transaction(function () use ($record, $data) {
            if ($record->isResolved()) {
                throw ValidationException::withMessages([
                    'status' => ['Downtime record is already resolved'],
                ]);
            }

            $startTime = isset($data['start_time'])
                ? Carbon::parse($data['start_time'])
                : $record->start_time;
            $endTime = array_key_exists('end_time', $data)
                ? ($data['end_time'] !== null ? Carbon::parse($data['end_time']) : null)
                : $record->end_time;

            if ($endTime && ! $endTime->greaterThan($startTime)) {
                throw ValidationException::withMessages([
                    'end_time' => ['End time must be after start time.'],
                ]);
            }

            $payload = collect($data)
                ->except([
                    'source_component_ids',
                    'affected_component_ids',
                    'location_ids',
                    'duration',
                ])
                ->all();

            if (isset($data['start_time'])) {
                $payload['start_time'] = $startTime;
            }
            if (array_key_exists('end_time', $data)) {
                $payload['end_time'] = $endTime;
            }

            if (array_key_exists('end_time', $data) || isset($data['start_time'])) {
                $payload['duration'] = $endTime ? (int) $startTime->diffInMinutes($endTime) : null;
                $payload['status'] = $endTime
                    ? DowntimeStatus::Resolved->value
                    : DowntimeStatus::Ongoing->value;
            }

            $record->update($payload);

            if (array_key_exists('location_ids', $data)) {
                $locationIds = $this->normalizeIds($data['location_ids'] ?? []);
                $this->assertActiveLocations($locationIds);
                $record->locations()->sync($locationIds);
            }

            if (array_key_exists('source_component_ids', $data) || array_key_exists('affected_component_ids', $data)) {
                $sourceIds = array_key_exists('source_component_ids', $data)
                    ? $this->normalizeComponentIds($data['source_component_ids'] ?? [])
                    : $record->sourceComponents()->pluck('downtime_components.id')->all();
                $affectedIds = array_key_exists('affected_component_ids', $data)
                    ? $this->normalizeComponentIds($data['affected_component_ids'] ?? [])
                    : $record->affectedComponents()->pluck('downtime_components.id')->all();

                $this->assertActiveComponents([...$sourceIds, ...$affectedIds]);
                $this->assertNoRoleOverlap($sourceIds, $affectedIds);
                $this->syncRecordComponents($record, $sourceIds, $affectedIds);
            }

            return $this->loadRecord($record->fresh());
        });
    }

    public function resolve(DowntimeRecord $record, array $data): DowntimeRecord
    {
        return DB::transaction(function () use ($record, $data) {
            if ($record->isResolved()) {
                throw ValidationException::withMessages([
                    'status' => ['Downtime record is already resolved'],
                ]);
            }

            $endTime = Carbon::parse($data['end_time']);
            if (! $endTime->greaterThan($record->start_time)) {
                throw ValidationException::withMessages([
                    'end_time' => ['End time must be after start time.'],
                ]);
            }

            $record->update([
                'status' => DowntimeStatus::Resolved->value,
                'end_time' => $endTime,
                'root_cause' => $data['root_cause'],
                'preventive_measures' => $data['preventive_measures'],
                'affected_users' => $data['affected_users'] ?? $record->affected_users,
                'estimated_cost' => $data['estimated_cost'] ?? $record->estimated_cost,
                'duration' => (int) $record->start_time->diffInMinutes($endTime),
            ]);

            return $this->loadRecord($record->fresh());
        });
    }

    public function delete(DowntimeRecord $record): void
    {
        if ($record->isResolved()) {
            throw ValidationException::withMessages([
                'status' => ['Resolved downtime record cannot be deleted.'],
            ]);
        }

        $record->delete();
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return DowntimeRecord::query()
            ->with($this->defaultRelations())
            ->when(isset($filters['type']) && $filters['type'] !== '', fn ($q) => $q->where('type', $filters['type']))
            ->when(isset($filters['status']) && $filters['status'] !== '', fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['impact']) && $filters['impact'] !== '', fn ($q) => $q->where('impact', $filters['impact']))
            ->when(isset($filters['location_id']) && $filters['location_id'] !== '', function ($q) use ($filters) {
                $q->whereHas('locations', fn ($inner) => $inner->where('downtime_locations.id', $filters['location_id']));
            })
            ->when(isset($filters['component_id']) && $filters['component_id'] !== '', function ($q) use ($filters) {
                $q->whereHas('recordComponents', fn ($inner) => $inner->where('component_id', $filters['component_id']));
            })
            ->when(isset($filters['category']) && $filters['category'] !== '', function ($q) use ($filters) {
                $q->whereHas('recordComponents.component', fn ($inner) => $inner->where('category', $filters['category']));
            })
            ->when(isset($filters['from_date']) && $filters['from_date'] !== '', function ($q) use ($filters) {
                $q->where('start_time', '>=', Carbon::parse($filters['from_date'])->startOfDay());
            })
            ->when(isset($filters['to_date']) && $filters['to_date'] !== '', function ($q) use ($filters) {
                $q->where('start_time', '<=', Carbon::parse($filters['to_date'])->endOfDay());
            })
            ->latest('start_time')
            ->paginate(min($perPage, 50));
    }

    public function loadRecord(DowntimeRecord $record): DowntimeRecord
    {
        return $record->load($this->defaultRelations());
    }

    private function defaultRelations(): array
    {
        return [
            'reporter:id,name,username',
            'locations:id,code,name,is_active',
            'sourceComponents:id,code,name,category,is_active',
            'affectedComponents:id,code,name,category,is_active',
        ];
    }

    private function syncRecordComponents(DowntimeRecord $record, array $sourceIds, array $affectedIds): void
    {
        DowntimeRecordComponent::where('downtime_id', $record->id)->delete();

        $now = now();
        $rows = [];

        foreach ($sourceIds as $componentId) {
            $rows[] = [
                'downtime_id' => $record->id,
                'component_id' => $componentId,
                'role' => DowntimeComponentRole::Source->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($affectedIds as $componentId) {
            $rows[] = [
                'downtime_id' => $record->id,
                'component_id' => $componentId,
                'role' => DowntimeComponentRole::Affected->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DowntimeRecordComponent::insert($rows);
        }
    }

    private function normalizeComponentIds(array $ids): array
    {
        return $this->normalizeIds($ids);
    }

    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function assertActiveLocations(array $ids): void
    {
        if ($ids === []) {
            throw ValidationException::withMessages([
                'location_ids' => ['Select at least one active location.'],
            ]);
        }

        $activeCount = DowntimeLocation::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->count();

        if ($activeCount !== count($ids)) {
            throw ValidationException::withMessages([
                'location_ids' => ['All selected locations must exist and be active.'],
            ]);
        }
    }

    private function assertActiveComponents(array $ids): void
    {
        $ids = $this->normalizeComponentIds($ids);
        if ($ids === []) {
            return;
        }

        $activeCount = DowntimeComponent::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->count();

        if ($activeCount !== count($ids)) {
            throw ValidationException::withMessages([
                'source_component_ids' => ['All selected components must exist and be active.'],
            ]);
        }
    }

    private function assertNoRoleOverlap(array $sourceIds, array $affectedIds): void
    {
        $overlap = array_values(array_intersect($sourceIds, $affectedIds));
        if ($overlap !== []) {
            throw ValidationException::withMessages([
                'affected_component_ids' => ['A component cannot be both directly down and affected in the same event.'],
            ]);
        }
    }

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

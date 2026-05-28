<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\TimelinePhase;
use App\Http\Requests\TimelineEntries\UpdateTimelineEntryRequest;
use App\Models\FeatureRequest;
use App\Models\TimelineEntry;
use App\Services\Log\ActivityLogService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Type\Time;

class TimelineEntryService
{
    public function __construct(
        protected readonly ActivityLogService $logService
    ) {}

    public function store(FeatureRequest $feature, array $data): TimelineEntry
    {
        $existingPhase = $feature->timelineEntries()
            ->where('phase', $data['phase'])
            ->exists();

        if ($existingPhase) {
            throw ValidationException::withMessages([
                'phase' => [
                    "Phase {$data['phase']} already exists for this request."
                ]
            ]);
        }

        $entry = $feature->timelineEntries()->create([
            ...$data,
            'progress' => $data['progress'] ?? 0,
            'is_completed' => false
        ]);

        $this->logService->log(
            loggable: $feature,
            action: ActivityAction::Updated,
            description: "Timeline entry '{$entry->title}' added for phase '{$entry->phase->value}'.",
            performedBy: Auth::id(),
            details: [
                'timeline_entry_id' => $entry->id,
                'title' => $entry->title,
                'phase' => $entry->phase->value,
            ],
        );

        return $entry->load('assignee');
    }

    public function update(TimelineEntry $entry, array $data): TimelineEntry
    {
        if ($entry->isCompleted()) [
            throw ValidationException::withMessages([
                'is_completed' => ['Completed timeline entry cannot be edited.']
            ])
        ];

        if (isset($data['progress']) && (int) $data['progress'] === 100) {
            return $this->complete($entry);
        }

        $entry->update($data);

        return $entry->load('assignee');
    }

    public function updateProgress(TimelineEntry $entry, int $progress): TimelineEntry
    {
        if ($entry->isCompleted()) [
            throw ValidationException::withMessages([
                'is_completed' => ['Completed timeline entry cannot be edited.']
            ])
        ];

        if ($progress === 100) {
            return $this->complete($entry);
        }

        $entry->update(['progress' => $progress]);

        return $entry->load('assignee');    
    }

    public function complete(TimelineEntry $entry): TimelineEntry
    {
        if ($entry->isCompleted()) {
            throw ValidationException::withMessages([
                'is_completed' => ['Timeline entry is already completed']
            ]);
        }

        $entry->update([
            'is_completed' => true,
            'progress' => 100,
            'end_date' => $entry->end_date ?? now()
        ]);

        $entry->loadMissing('featureRequest');

        $this->logService->logMilestoneReached(
            loggable: $entry->featureRequest,
            milestone: "Phase '{$entry->phase->label()}' Completed.",
            details: [
                'timeline_entry_id' => $entry->id,
                'title' => $entry->title,
                'phase' => $entry->phase->value,
                'completed_at' => now()->format('Y-m-d H:i:s'),
            ]
        );

        $this->syncFeatureRequestProgress($entry->featureRequest);

        return $entry->load('assignee');
    }

    public function delete(TimelineEntry $entry): void
    {
        if ($entry->isCompleted()) {
            throw ValidationException::withMessages([
                'is_completed' => ['Timeline entry is already completed']
            ]);
        }

        $entry->delete();
    }

    // Helpers
    private function syncFeatureRequestProgress(FeatureRequest $feature): void
    {
        $overallProgress = $feature->calculateTimelineProgress();
        $feature->update(['progress' => $overallProgress]);
    }

    //* Query
    public function getByFeatureRequest(FeatureRequest $feature, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $phaseOrder = collect(TimelinePhase::ordered())
        ->mapWithKeys(fn ($phase, $index) => [$phase->value => $index + 1])
        ->toArray();

        return $feature->timelineEntries()
        ->with('assignee:id,name,username')
        ->when(
            isset($filters['phase']),
            fn ($q) => $q->where('phase', $filters['phase'])
        )
        ->when(
            isset($filters['is_completed']),
            fn ($q) => $q->where(
                'is_completed', 
                filter_var($filters['is_completed'], FILTER_VALIDATE_BOOLEAN)
            )
        )
        ->when(
            isset($filters['assigned_to']),
            fn ($q) => $q->where('assigned_to', $filters['assigned_to'])
        )
        ->orderByRaw("FIELD(phase, '" . implode("','", array_keys($phaseOrder)) . "')")
        ->paginate(min($perPage, 50));
    }
}

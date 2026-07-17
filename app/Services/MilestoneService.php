<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\FeatureRequest;
use App\Models\Milestone;
use App\Services\Log\ActivityLogService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MilestoneService
{
    public function __construct(
        private readonly ActivityLogService $logService
    ) {}

    public function store(FeatureRequest $feature, array $data): Milestone
    {
        $currentStatus = $feature->status->value;

        if (in_array($currentStatus, ['completed', 'rejected', 'closed'])) {
            throw ValidationException::withMessages([
                'feature_request' => [
                    "Cannot add milestone to feature request with status '{$currentStatus}'."
                ] 
            ]);
        }

        $milestone = $feature->milestones()->create([
            ...$data,
            'progress' => $data['progress'] ?? 0,
            'created_by' => Auth::id(),
        ]);

        $this->syncFeatureRequestProgress($feature);

        $this->logService->log(
            loggable: $feature,
            action: ActivityAction::Created,
            description: "Milestone {$milestone->title} was added.",
            performedBy: Auth::id(),
            details: [
                'milestone_id' => $milestone->id,
                'milestone_title' => $milestone->title,
                'target_date' => $milestone->target_date?->format('Y-m-d H:i:s'),
            ]
        );

        return $milestone->load('creator');
    }

    public function update(Milestone $milestone, array $data): Milestone
    {
        if ($milestone->isCompleted()) {
            throw ValidationException::withMessages([
                'is_completed' => ['Completed milestone cannot be edited.']
            ]);
        }

        if (isset($data['progress']) && (int) $data['progress'] === 100) {
            return $this->complete($milestone);
        }

        $milestone->update($data);

        $this->syncFeatureRequestProgress($milestone->featureRequest);

        return $milestone->load('creator');
    }

    public function updateProgress(Milestone $milestone, int $progress): Milestone
    {
        if ($milestone->isCompleted()) {
            throw ValidationException::withMessages([
                'progress' => ['Cannot update progress of a completed milestone.']
            ]);
        }

        if ($progress === 100) {
            return $this->complete($milestone);
        }

        $milestone->update(['progress' => $progress]);

        $this->syncFeatureRequestProgress($milestone->featureRequest);

        return $milestone->load('creator');
    }

    public function complete(Milestone $milestone): Milestone
    {
        if ($milestone->isCompleted()) {
            throw ValidationException::withMessages([
                'is_completed' => ['Milestone is already completed']
            ]);
        }

        $milestone->update([
            'is_completed' => true,
            'progress' => 100,
            'completed_date' => now()
        ]);

        $milestone->loadMissing('featureRequest');

        $this->logService->logMilestoneReached(
            loggable: $milestone->featureRequest,
            milestone: $milestone->title,
            details: [
                'milestone_id' => $milestone->id,
                'completed_date' => now()->format('Y-m-d H:i:s'),
            ]
        );

        $this->syncFeatureRequestProgress($milestone->featureRequest);

        return $milestone->load('creator');
    }

    public function delete(Milestone $milestone): void
    {
        if ($milestone->isCompleted()) {
            throw ValidationException::withMessages([
                'is_completed' => ['Completed milestone cannot be deleted']
            ]);
        }

        $feature = $milestone->featureRequest;

        $milestone->delete();

        $this->syncFeatureRequestProgress($feature);
    }

    //* Query
    public function getByFeatureRequest(
        FeatureRequest $feature,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        return $feature->milestones()
        ->with('creator:id,name,username')
        ->when(isset($filters['is_completed']),
            fn ($q) => $q->where('is_completed', filter_var($filters['is_completed'], FILTER_VALIDATE_BOOLEAN))
            )
        ->when(isset($filters['overdue']) && $filters['overdue'], 
            fn ($q) => $q->where('is_completed', false)
            ->where('target_date', '<', now())
            )
        ->orderBy('target_date')
        ->paginate(min($perPage, 50));
    }

    // Helper
    private function syncFeatureRequestProgress(FeatureRequest $feature): void
    {
        $feature->refresh();
        $feature->syncProgressFromMilestones();
    }
}
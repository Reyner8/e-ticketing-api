<?php

namespace App\Services;

use App\Models\DowntimeComponent;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DowntimeComponentService
{
    public function getAll(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return DowntimeComponent::query()
            ->with([
                'creator:id,name,username',
                'defaultAffectedComponents:id,code,name,category,is_active',
            ])
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['category']) && $filters['category'] !== '', fn ($q) => $q->where('category', $filters['category']))
            ->when(array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '', function ($q) use ($filters) {
                $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('name')
            ->paginate(min($perPage, 100));
    }

    public function store(array $data): DowntimeComponent
    {
        return DB::transaction(function () use ($data) {
            $component = DowntimeComponent::create([
                'code' => $data['code'] ?? $this->uniqueCode($data['name']),
                'name' => trim($data['name']),
                'category' => $data['category'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => Auth::id(),
            ]);

            if (! empty($data['default_affected_component_ids'])) {
                $this->syncDependencies($component, $data['default_affected_component_ids']);
            }

            return $component->load([
                'creator:id,name,username',
                'defaultAffectedComponents:id,code,name,category,is_active',
            ]);
        });
    }

    public function update(DowntimeComponent $component, array $data): DowntimeComponent
    {
        return DB::transaction(function () use ($component, $data) {
            $component->update([
                'code' => $data['code'] ?? $component->code,
                'name' => isset($data['name']) ? trim($data['name']) : $component->name,
                'category' => $data['category'] ?? $component->category,
                'description' => array_key_exists('description', $data) ? $data['description'] : $component->description,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $component->is_active,
            ]);

            if (array_key_exists('default_affected_component_ids', $data)) {
                $this->syncDependencies($component, $data['default_affected_component_ids'] ?? []);
            }

            return $component->fresh()->load([
                'creator:id,name,username',
                'defaultAffectedComponents:id,code,name,category,is_active',
            ]);
        });
    }

    public function syncDependencies(DowntimeComponent $component, array $affectedIds): DowntimeComponent
    {
        $affectedIds = collect($affectedIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === $component->id)
            ->values()
            ->all();

        if ($affectedIds !== []) {
            $activeCount = DowntimeComponent::query()
                ->whereIn('id', $affectedIds)
                ->where('is_active', true)
                ->count();

            if ($activeCount !== count($affectedIds)) {
                throw ValidationException::withMessages([
                    'default_affected_component_ids' => ['All affected components must exist and be active.'],
                ]);
            }
        }

        $component->defaultAffectedComponents()->sync($affectedIds);

        return $component->load('defaultAffectedComponents:id,code,name,category,is_active');
    }

    public function suggestAffected(array $sourceComponentIds): Collection
    {
        $sourceComponentIds = collect($sourceComponentIds)->map(fn ($id) => (int) $id)->unique()->values();

        if ($sourceComponentIds->isEmpty()) {
            return collect();
        }

        $affectedIds = DB::table('downtime_component_dependencies')
            ->whereIn('source_component_id', $sourceComponentIds)
            ->pluck('affected_component_id')
            ->unique()
            ->reject(fn ($id) => $sourceComponentIds->contains((int) $id))
            ->values();

        if ($affectedIds->isEmpty()) {
            return collect();
        }

        return DowntimeComponent::query()
            ->where('is_active', true)
            ->whereIn('id', $affectedIds)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'category', 'is_active']);
    }

    public function deactivate(DowntimeComponent $component): DowntimeComponent
    {
        $component->update(['is_active' => false]);

        return $component->fresh()->load([
            'creator:id,name,username',
            'defaultAffectedComponents:id,code,name,category,is_active',
        ]);
    }

    public function delete(DowntimeComponent $component): void
    {
        if ($component->isReferenced()) {
            throw ValidationException::withMessages([
                'component' => ['Component is referenced by downtime history. Deactivate it instead of deleting.'],
            ]);
        }

        DB::transaction(function () use ($component) {
            $component->defaultAffectedComponents()->detach();
            $component->defaultSourceComponents()->detach();
            $component->delete();
        });
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'component';
        }

        $code = $base;
        $suffix = 1;
        while (DowntimeComponent::where('code', $code)->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }
}

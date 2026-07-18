<?php

namespace App\Services;

use App\Models\DowntimeLocation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DowntimeLocationService
{
    public function getAll(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return DowntimeLocation::query()
            ->with('creator:id,name,username')
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '', function ($q) use ($filters) {
                $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('name')
            ->paginate(min($perPage, 100));
    }

    public function store(array $data): DowntimeLocation
    {
        return DowntimeLocation::create([
            'code' => $data['code'] ?? $this->uniqueCode($data['name']),
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => Auth::id(),
        ])->load('creator:id,name,username');
    }

    public function update(DowntimeLocation $location, array $data): DowntimeLocation
    {
        $location->update([
            'code' => $data['code'] ?? $location->code,
            'name' => isset($data['name']) ? trim($data['name']) : $location->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $location->description,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $location->is_active,
        ]);

        return $location->fresh()->load('creator:id,name,username');
    }

    public function deactivate(DowntimeLocation $location): DowntimeLocation
    {
        $location->update(['is_active' => false]);

        return $location->fresh()->load('creator:id,name,username');
    }

    public function delete(DowntimeLocation $location): void
    {
        if ($location->isReferenced()) {
            throw ValidationException::withMessages([
                'location' => ['Location is referenced by downtime history. Deactivate it instead of deleting.'],
            ]);
        }

        $location->delete();
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'location';
        }

        $code = $base;
        $suffix = 1;
        while (DowntimeLocation::where('code', $code)->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }
}

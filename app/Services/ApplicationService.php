<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function getAll(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return Application::query()
            ->with('creator:id,name,username')
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($q) use ($filters) {
                $search = $filters['search'];
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(
                array_key_exists('is_active', $filters)
                    && $filters['is_active'] !== null
                    && $filters['is_active'] !== '',
                function ($q) use ($filters) {
                    $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
                }
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(min($perPage, 100));
    }

    public function store(array $data): Application
    {
        return Application::create([
            'code' => $data['code'] ?? $this->uniqueCode($data['name']),
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
            'created_by' => Auth::id(),
        ])->load('creator:id,name,username');
    }

    public function update(Application $application, array $data): Application
    {
        $application->update([
            'code' => $data['code'] ?? $application->code,
            'name' => isset($data['name']) ? trim($data['name']) : $application->name,
            'description' => array_key_exists('description', $data)
                ? $data['description']
                : $application->description,
            'is_active' => array_key_exists('is_active', $data)
                ? (bool) $data['is_active']
                : $application->is_active,
            'sort_order' => array_key_exists('sort_order', $data)
                ? (int) $data['sort_order']
                : $application->sort_order,
        ]);

        return $application->fresh()->load('creator:id,name,username');
    }

    public function deactivate(Application $application): Application
    {
        $application->update(['is_active' => false]);

        return $application->fresh()->load('creator:id,name,username');
    }

    public function delete(Application $application): void
    {
        if ($application->isReferenced()) {
            throw ValidationException::withMessages([
                'application' => ['Application is referenced by records. Deactivate it instead of deleting.'],
            ]);
        }

        $application->delete();
    }

    public static function activeCodeExistsRule(): Exists
    {
        return Rule::exists('applications', 'code')->where(fn ($q) => $q->where('is_active', true));
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'app';
        }

        $code = $base;
        $suffix = 1;
        while (Application::where('code', $code)->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }
}

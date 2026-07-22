<?php

namespace App\Services\Ops;

use App\Enums\InspectionConclusion;
use App\Enums\InspectionType;
use App\Models\ServerRoomInspection;
use App\Support\SequentialIdGenerator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ServerRoomInspectionService
{
    /** Peralatan ruang server yang dicek per inspeksi. */
    public const CHECKLIST_KEYS = [
        'ups',
        'cable',
        'rack',
        'ac',
        'pc_server',
        'mikrotik',
        'switch',
    ];

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return ServerRoomInspection::query()
            ->with(['inspector:id,name,username', 'creator:id,name,username'])
            ->when(
                isset($filters['inspection_type']),
                fn ($q) => $q->where('inspection_type', $filters['inspection_type'])
            )
            ->when(
                isset($filters['conclusion']),
                fn ($q) => $q->where('conclusion', $filters['conclusion'])
            )
            ->when(
                isset($filters['from']),
                fn ($q) => $q->whereDate('inspection_date', '>=', $filters['from'])
            )
            ->when(
                isset($filters['to']),
                fn ($q) => $q->whereDate('inspection_date', '<=', $filters['to'])
            )
            ->when(
                isset($filters['search']),
                function ($q) use ($filters) {
                    $term = '%'.$filters['search'].'%';

                    return $q->where(function ($inner) use ($term) {
                        $inner->where('id', 'like', $term)
                            ->orWhere('notes', 'like', $term)
                            ->orWhere('follow_up', 'like', $term);
                    });
                }
            )
            ->latest('inspection_date')
            ->latest('id')
            ->paginate(min($perPage, 50));
    }

    public function store(array $data): ServerRoomInspection
    {
        $data['id'] = SequentialIdGenerator::next(ServerRoomInspection::class, 'INSP');
        $data['inspector_id'] = $data['inspector_id'] ?? Auth::id();
        $data['created_by'] = Auth::id();
        $data['checklist_items'] = $this->normalizeChecklist($data['checklist_items'] ?? []);
        $data['inspection_type'] = InspectionType::from($data['inspection_type'])->value;
        $data['conclusion'] = InspectionConclusion::from($data['conclusion'])->value;

        return ServerRoomInspection::create($data)->load(['inspector', 'creator']);
    }

    public function update(ServerRoomInspection $inspection, array $data): ServerRoomInspection
    {
        if (isset($data['checklist_items'])) {
            $data['checklist_items'] = $this->normalizeChecklist($data['checklist_items']);
        }
        if (isset($data['inspection_type'])) {
            $data['inspection_type'] = InspectionType::from($data['inspection_type'])->value;
        }
        if (isset($data['conclusion'])) {
            $data['conclusion'] = InspectionConclusion::from($data['conclusion'])->value;
        }

        $inspection->update($data);

        return $inspection->fresh()->load(['inspector', 'creator']);
    }

    public function delete(ServerRoomInspection $inspection): void
    {
        $inspection->delete();
    }

    /**
     * @param  array<string, mixed>  $items
     * @return array<string, array{ok: bool, notes: string|null}>
     */
    private function normalizeChecklist(array $items): array
    {
        $normalized = [];

        foreach (self::CHECKLIST_KEYS as $key) {
            if (! array_key_exists($key, $items)) {
                throw ValidationException::withMessages([
                    "checklist_items.{$key}" => ["Checklist item [{$key}] is required."],
                ]);
            }

            $item = $items[$key];
            if (! is_array($item) || ! array_key_exists('ok', $item)) {
                throw ValidationException::withMessages([
                    "checklist_items.{$key}" => ["Checklist item [{$key}] must include ok boolean."],
                ]);
            }

            $normalized[$key] = [
                'ok' => (bool) $item['ok'],
                'notes' => isset($item['notes']) ? (string) $item['notes'] : null,
            ];
        }

        return $normalized;
    }
}

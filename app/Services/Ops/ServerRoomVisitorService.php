<?php

namespace App\Services\Ops;

use App\Enums\VisitorStatus;
use App\Models\ServerRoomVisitor;
use App\Support\SequentialIdGenerator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ServerRoomVisitorService
{
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return ServerRoomVisitor::query()
            ->with(['escort:id,name,username', 'creator:id,name,username'])
            ->when(
                isset($filters['status']),
                fn ($q) => $q->where('status', $filters['status'])
            )
            ->when(
                isset($filters['from']),
                fn ($q) => $q->where('entry_at', '>=', $filters['from'])
            )
            ->when(
                isset($filters['to']),
                fn ($q) => $q->where('entry_at', '<=', $filters['to'])
            )
            ->when(
                isset($filters['search']),
                function ($q) use ($filters) {
                    $term = '%'.$filters['search'].'%';

                    return $q->where(function ($inner) use ($term) {
                        $inner->where('id', 'like', $term)
                            ->orWhere('visitor_name', 'like', $term)
                            ->orWhere('unit_or_vendor', 'like', $term)
                            ->orWhere('purpose', 'like', $term)
                            ->orWhere('identity_document', 'like', $term);
                    });
                }
            )
            ->latest('entry_at')
            ->latest('id')
            ->paginate(min($perPage, 50));
    }

    public function store(array $data): ServerRoomVisitor
    {
        $data['id'] = SequentialIdGenerator::next(ServerRoomVisitor::class, 'VIS');
        $data['escorted_by'] = $data['escorted_by'] ?? Auth::id();
        $data['created_by'] = Auth::id();
        $data['status'] = VisitorStatus::Inside->value;
        $data['exit_at'] = null;

        return ServerRoomVisitor::create($data)->load(['escort', 'creator']);
    }

    public function update(ServerRoomVisitor $visitor, array $data): ServerRoomVisitor
    {
        if (isset($data['status'])) {
            $data['status'] = VisitorStatus::from($data['status'])->value;
        }

        $visitor->update($data);

        return $visitor->fresh()->load(['escort', 'creator']);
    }

    public function checkout(ServerRoomVisitor $visitor, ?string $exitAt = null): ServerRoomVisitor
    {
        if ($visitor->status === VisitorStatus::Completed) {
            throw ValidationException::withMessages([
                'status' => ['Visitor already checked out.'],
            ]);
        }

        $exit = $exitAt ? \Carbon\Carbon::parse($exitAt) : now();
        if ($exit->lt($visitor->entry_at)) {
            throw ValidationException::withMessages([
                'exit_at' => ['Exit time must be after entry time.'],
            ]);
        }

        $visitor->update([
            'exit_at' => $exit,
            'status' => VisitorStatus::Completed->value,
        ]);

        return $visitor->fresh()->load(['escort', 'creator']);
    }

    public function delete(ServerRoomVisitor $visitor): void
    {
        $visitor->delete();
    }
}

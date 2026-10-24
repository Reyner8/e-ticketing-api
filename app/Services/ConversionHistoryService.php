<?php

namespace App\Services;

use App\Enums\ConversionTypes;
use App\Models\ConversionHistory;
use App\Models\Ticket;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ConversionHistoryService
{
    public function record(
        Ticket $ticket,
        ConversionTypes $targetType,
        string $targetId,
        ?string $reason = null,
        ?string $notes = null,
    ): ConversionHistory {
        return ConversionHistory::create([
            'source_ticket_id' => $ticket->id,
            'target_type' => $targetType->value,
            'target_id' => $targetId,
            'converted_by' => Auth::id(),
            'reason' => $reason,
            'notes' => $notes
        ]);
    }

    //* Query
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return ConversionHistory::query()
            ->with([
                'sourceTicket:id,title,status',
                'converter:id,name,username'
            ])
            ->when(
                isset($filters['target_type']),
                fn($q) => $q->where('target_type', $filters['target_type'])
            )
            ->when(
                isset($filters['converted_by']),
                fn($q) => $q->where('converted_by', $filters['converted_by'])
            )
            ->when(
                isset($filters['from_date']),
                fn($q) => $q->where('converted_at', '>=', $filters['from_date'])
            )
            ->when(
                isset($filters['to_date']),
                fn($q) => $q->where('converted_at', '<=', $filters['to_date'])
            )
            ->latest('converted_at')
            ->paginate(min($perPage, 50));
    }

    public function getByTicket(Ticket $ticket): ?ConversionHistory
    {
        return ConversionHistory::where('source_ticket_id', $ticket->id)
            ->with([
                'sourceTicket:id,title,status',
                'converter:id,name,username',
            ])
            ->latest('converted_at')
            ->first();
    }
}

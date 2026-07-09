<?php

namespace App\Services;

use App\Enums\Priorities;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Ticket;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function store(array $data): Ticket
    {
        $ticket = DB::transaction(function () use ($data) {
            $ticket = Ticket::create([
                ...$data,
                'id'  => $this->generateTicketId(),
                'reporter_id' => Auth::id(),
                'status' => TicketStatus::Draft->value,
                'date_reported' => now(),
                'due_date' => $data['due_date'] ??
                    $this->calculateDueDate($data['priority'])
            ]);

            return $ticket;
        });

        return $ticket->load(['reporter', 'assignedUser', 'tags']);
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        $ticket->update($data);

        return $ticket->load(['reporter', 'assignedUser', 'tags']);
    }

    public function delete(Ticket $ticket): void
    {
        $ticket->delete();
    }

    //* Query
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $user = Auth::user();

        return Ticket::query()
            ->with(['reporter:id,name,username', 'assignedUser:id,name,username', 'tags'])
            ->when(
                $user->role === UserRole::Reporter,
                fn($q) => $q->where('reporter_id', $user->id)
            )
            ->when(
                isset($filters['status']),
                fn($q) => $q->byStatus($filters['status'])
            )
            ->when(
                isset($filters['priority']),
                fn($q) => $q->byPriority($filters['priority'])
            )
            ->when(
                isset($filters['category']),
                fn($q) => $q->byCategory($filters['category'])
            )
            ->when(
                isset($filters['assigned_team']),
                fn($q) => $q->where('assigned_team', $filters['assigned_team'])
            )
            ->when(
                isset($filters['reporter_id']),
                fn($q) => $q->where('reporter_id', $filters['reporter_id'])
            )
            ->when(
                isset($filters['sla_breached']),
                fn($q) => $q->slaBreached()
            )
            ->when(
                isset($filters['overdue']),
                fn($q) => $q->overdue()
            )
            ->when(
                ! empty($filters['tags']),
                fn($q) => $q->withAnyTags($filters['tags'])
            )
            ->when(
                isset($filters['search']),
                fn($q) => $q->where('title', 'like', '%' . $filters['search'] . '%')
            )
            ->when(
                isset($filters['is_public_submission']),
                fn($q) => $q->where(
                    'is_public_submission',
                    filter_var($filters['is_public_submission'], FILTER_VALIDATE_BOOLEAN)
                )
            )
            ->latest('date_reported')
            ->paginate(min($perPage, 50));
    }

    // Private
    private function generateTicketId(): string
    {
        return DB::transaction(function () {
            $prefix = 'TKT';
            $year = now()->format('Y');

            $lastTicket = Ticket::where('id', 'like', "{$prefix}-{$year}-%")
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $lastNumber = $lastTicket ? (int) substr($lastTicket->id, -3) : 0;
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

            return "{$prefix}-{$year}-{$newNumber}";
        });
    }

    private function calculateDueDate(string $priority): Carbon
    {
        $priorityEnum = Priorities::tryFrom($priority);
        $hours = $priorityEnum ? $priorityEnum->slaHours() : 48;

        return now()->addHours($hours);
    }
}

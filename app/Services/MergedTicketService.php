<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\Ticket;
use App\Services\Log\ActivityLogService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use tidy;

class MergedTicketService
{
    public function __construct(
        private readonly ActivityLogService $logService
    ) {}

    public function merge(Ticket $parentTicket, array $mergedTicketIds): Collection
    {
        $this->guardParentTicket($parentTicket);

        $ticketsToMerged = Ticket::whereIn('id', $mergedTicketIds)->get();

        foreach ($ticketsToMerged as $ticket) {
            $this->guardMergedTicket($parentTicket, $ticket);
        }

        DB::transaction(function () use ($parentTicket, $ticketsToMerged) {
            foreach ($ticketsToMerged as $ticket) {
                $parentTicket->mergedTickets()->attach($ticket->id, [
                    'merged_by' => Auth::id(),
                    'merged_at' => now(),
                ]);

                $ticket->update([
                    'parent_ticket_id' => $parentTicket->id,
                    'status' => 'closed',
                    'closed_date' => now(),
                ]);
            }

            $mergedIds = $ticketsToMerged->pluck('id')->implode(', ');

            $this->logService->log(
                loggable: $parentTicket,
                action: ActivityAction::Updated,
                description: "Tickets merged into this ticket: {$mergedIds}",
                performedBy: Auth::id(),
                details: [
                    'merged_ticket_ids' => $ticketsToMerged->pluck('id')->toArray(),
                    'merged_count' => $ticketsToMerged->count()
                ]
            );
        });

        return $this->getMergedTickets($parentTicket);
    }

    public function unmerge(Ticket $parentTicket, string $mergedTicketId): Collection
    {
        $isMerged = $parentTicket->mergedTickets()
            ->where('merged_ticket_id', $mergedTicketId)
            ->exists();

        if (! $isMerged) {
            throw ValidationException::withMessages([
                'merged_ticket_id' => [
                    "Ticket '{$mergedTicketId}' is not merged into this ticket."
                ]
            ]);
        }

        DB::transaction(function () use ($parentTicket, $mergedTicketId) {
            $parentTicket->mergedTickets()->detach($mergedTicketId);

            Ticket::where('id', $mergedTicketId)->update([
                'status' => 'draft',
                'closed_date' => null
            ]);

            $this->logService->log(
                loggable: $parentTicket,
                action: ActivityAction::Updated,
                description: "Ticket '{$mergedTicketId}' has been unmerged.",
                performedBy: Auth::id(),
                details: ['unmerged_ticket_id' => $mergedTicketId]
            );
        });

        return $this->getMergedTickets($parentTicket);
    }

    //* Query
    public function getMergedTickets(Ticket $parentTicket): Collection
    {
        return $parentTicket->mergedTickets()
            ->with(['reporter:id,name,username'])
            ->get();
    }

    // Private
    private function guardParentTicket(Ticket $ticket): void
    {
        $status = $ticket->status->value;

        if (in_array('status', ['closed', 'converted'])) {
            throw ValidationException::withMessages([
                'parent_ticket_id' => [
                    "Cannot merge into a ticket with status '{$status}'."
                ]
            ]);
        }
    }

    private function guardMergedTicket(Ticket $parentTicket, Ticket $ticket): void
    {
        $alreadyMerge = $parentTicket->mergedTickets()
            ->where('merged_ticket_id', $ticket->id)
            ->exists();

        $status = $ticket->status->value;

        if ($alreadyMerge) {
            throw ValidationException::withMessages([
                'merged_ticket_ids' => [
                    "Ticket '{$ticket->id}' is already merged into this ticket."
                ]
            ]);
        }

        if ($ticket->isMerged()) {
            throw ValidationException::withMessages([
                'merged_ticket_ids' => [
                    "Ticket '{$ticket->id}' is already merged into another ticket."
                ]
            ]);
        }

        if (in_array('status', ['closed', 'converted'])) {
            throw ValidationException::withMessages([
                'merged_ticket_ids' => [
                    "Ticket '{$ticket->id}' with status {$status} cannot be merged."
                ]
            ]);
        }
    }
}

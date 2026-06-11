<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketUpdatedNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TicketWatcherService
{
    public function watch(Ticket $ticket, int $userId): Collection
    {
        if ($ticket->isWatchedBy($userId)) {
            throw ValidationException::withMessages([
                'user_id' => ['User is already watching this ticket']
            ]);
        }

        $ticket->watchers()->attach($userId, [
            'created_at' => now(),
        ]);
    
        return $this->getWatchers($ticket);
    }

    public function unwatch(Ticket $ticket, int $userId): Collection
    {
        if (! $ticket->isWatchedBy($userId)) {
            throw ValidationException::withMessages([
                'user_id' => ['User is not watching this ticket']
            ]);
        }

        $ticket->watchers()->detach($userId);

        return $this->getWatchers($ticket);
    }

    public function toggleWatch(Ticket $ticket): array
    {
        $userId = Auth::id();
        $isWatching = $ticket->isWatchedBy($userId);

        if ($isWatching) {
            $ticket->watchers()->detach($userId);
            $message = 'You are no longer watching this ticket';
        } else {
            $ticket->watchers()->attach($userId, [
                'created_at' => now()
            ]);
            $message = 'You are now watching this ticket';
        }

        return [
            'is_watching' => ! $isWatching,
            'message' => $message,
            'watchers' => $this->getWatchers($ticket)
        ];
    }

    //* Query
    public function getWatchers(Ticket $ticket): Collection
    {
        return $ticket->watchers()->get(['users.id', 'users.name', 'users.username']);
    }

    public function getWatchedTickets(int $userId, int $perPage): LengthAwarePaginator
    {
        return User::findOrFail($userId)
        ->watchedTickets()
        ->with(['reporter', 'assignedUser', 'tags'])
        ->latest('ticket_watchers.created_at')
        ->paginate(min($perPage, 50));
    }

    public function isWatching(Ticket $ticket, int $userId): bool
    {
        return $ticket->isWatchedBy($userId);
    }

    //* Notifications
    public function notifyWatchers(Ticket $ticket, string $event, array $details = []): void
    {
        $watchers = $ticket->watchers()
        ->where('users.id', '!=', Auth::id())
        ->get();

        if ($watchers->isEmpty()) {
            return;
        }

        foreach ($watchers as $watcher) {
            $watcher->notify(
                new TicketUpdatedNotification(
                    ticket: $ticket,
                    event: $event,
                    details: $details
                )
            );
        }
    }
}
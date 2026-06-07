<?php

namespace App\Services\CalendarEvent;

use App\Enums\EventTypes;
use App\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class CalendarEventService
{
    public function store(array $data): CalendarEvent
    {
        if (empty($data['color'])) {
            $type = EventTypes::from($data['type']);
            $data['color'] = $type->defaultColor();
        }

        return CalendarEvent::create([
            ...$data,
            'created_by' => Auth::id(),
        ]);
    }

    public function update(CalendarEvent $event, array $data): CalendarEvent
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($event->created_by !== Auth::id() && ! $user->isItStaff()) {
            throw ValidationException::withMessages([
                'event' => ['You are not authorized to update this event.']
            ]);
        }

        if (isset($data['recurring_frequency']) && is_null($data['recurring_frequency'])) {
            $data['recurring_interval'] = null;
            $data['recurring_end_date'] = null;
        }

        $event->update($data);

        return $event->load('creator');
    }

    public function delete(CalendarEvent $event): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($event->created_by !== Auth::id() && ! $user->isItStaff()) {
            throw ValidationException::withMessages([
                'event' => ['You are not authorized to update this event.']
            ]);
        }

        $event->delete();
    }

    //* Query
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return CalendarEvent::query()
            ->with('creator:id,name,username')
            ->when(
                isset($filters['type']),
                fn($q) => $q->byType($filters['type'])
            )
            ->when(
                isset($filters['from']) && isset($filters['to']),
                fn($q) => $q->where('start', '>=', $filters['from'])
                    ->where('start', '<=', $filters['to'])
            )
            ->when(
                isset($filters['from']) && ! isset($filters['to']),
                fn($q) => $q->where('start', '>=', $filters['from'])
            )
            ->when(
                isset($filters['recurring']),
                function ($q) use ($filters) {
                    $isRecurring = filter_var($filters['recurring'], FILTER_VALIDATE_BOOLEAN);
                    return $isRecurring ? $q->recurring() : $q->whereNull('recurring_frequency');
                }
            )
            ->when(
                isset($filters['created_by']),
                fn($q) => $q->where('created_by', $filters['created_by'])
            )
            ->orderBy('start')
            ->paginate(min($perPage, 50));
    }

    public function getForCalendar(string $from, string $to): Collection
    {
        return CalendarEvent::query()
            ->with('creator:id,name,username')
            ->where(function ($q) use ($from, $to) {
                $q->where('start', '<=', $to)
                    ->where('end', '>=', $from);
            })
            ->orderBy('start')
            ->get();
    }

    public function getUpcoming(int $days = 7): Collection
    {
        return CalendarEvent::query()
            ->with('creator:id,name,username')
            ->where('start', '>=', now())
            ->where('start', '<=', now()->addDays($days))
            ->orderBy('start')
            ->get();
    }
}

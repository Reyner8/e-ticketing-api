<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CalendarEvent\StoreCalendarEventRequest;
use App\Http\Requests\CalendarEvent\UpdateCalendarEventRequest;
use App\Http\Resources\CalendarEvent\CalendarEventResource;
use App\Models\CalendarEvent;
use App\Services\CalendarEvent\CalendarEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarEventController extends Controller
{
    public function __construct(
        private readonly CalendarEventService $calendarService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $events = $this->calendarService->getAll(
            filters: $request->only(['type', 'from', 'to', 'recurring', 'created_by']),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $events,
            CalendarEventResource::collection($events),
            'Calendar events retrieved successfully.'
        );
    }

    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        $event = $this->calendarService->store($request->validated());

        return ApiResponse::success(
            new CalendarEventResource($event),
            'Calendar event created successfully.',
            201
        );
    }

    public function show(CalendarEvent $event): JsonResponse
    {
        return ApiResponse::success(
            new CalendarEventResource($event->load('creator')),
            'Calendar event retrieved successfully.'
        );
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $event): JsonResponse
    {
        $event = $this->calendarService->update($event, $request->validated());

        return ApiResponse::success(
            new CalendarEventResource($event),
            'Calendar event updated successfully.'
        );
    }

    public function destroy(CalendarEvent $event): JsonResponse
    {
        $this->calendarService->delete($event);

        return ApiResponse::success(
            null,
            'Calendar event deleted successfully.'
        );
    }

    public function calendar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from']
        ]);

        $events = $this->calendarService->getForCalendar(
            from: $validated['from'],
            to: $validated['to']
        );

        return ApiResponse::success(
            CalendarEventResource::collection($events),
            'Calendar events retrieved successfully.'
        );
    }

    public function upcoming(Request $request): JsonResponse
    {
        $event = $this->calendarService->getUpcoming(
            days: $request->integer('days', 7)
        );

        return ApiResponse::success(
            CalendarEventResource::collection($event),
            'Upcoming events retrieved successfully.'
        );
    }
}

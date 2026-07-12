<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Ticket\TicketResource;
use App\Http\Resources\TicketWatcherResource;
use App\Models\Ticket;
use App\Services\TicketWatcherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketWatcherController extends Controller
{
    public function __construct(
        private readonly TicketWatcherService $watcherService
    ) {}

    public function index(Ticket $ticket) : JsonResponse 
    {
        $watchers = $this->watcherService->getWatchers($ticket);
        
        return ApiResponse::success(
            TicketWatcherResource::collection($watchers),
            'Ticket watchers retrieved successfully',
            201
        );
    }

    public function toggleWatch(Ticket $ticket): JsonResponse
    {
        $result = $this->watcherService->toggleWatch($ticket);

        return ApiResponse::success(
            [
                'is_watching' => $result['is_watching'],
                'watchers' => TicketWatcherResource::collection($result['watchers'])
            ],
            $result['message']
        );
    }

    public function watchedTickets(Request $request): JsonResponse
    {
        $tickets = $this->watcherService->getWatchedTickets(
            userId: Auth::id(),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $tickets,
            TicketResource::collection($tickets),
            'Watched tickets retrieved successfully.'
        );
    }

    public function status(Ticket $ticket): JsonResponse
    {
        $isWatching = $this->watcherService->isWatching(
            ticket: $ticket,
            userId: Auth::id()
        );

        return ApiResponse::success(
            [
                'is_watching' => $isWatching,
                'watchers_count' => $ticket->watchersCount(),
            ],
        );
    }

    public function addWatcher(Request $request, Ticket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $watchers = $this->watcherService->watch(
            ticket: $ticket,
            userId: $validated['user_id']
        );

        return ApiResponse::success(
            TicketWatcherResource::collection($watchers),
            'Watcher added successfully.'
        );
    }

    public function removeWatcher(Ticket $ticket, int $userId): JsonResponse
    {
        $watchers = $this->watcherService->unwatch(
            ticket: $ticket,
            userId: $userId
        );

        return ApiResponse::success(
            TicketWatcherResource::collection($watchers),
            'Watcher removed successfully.'
        );
    }
}

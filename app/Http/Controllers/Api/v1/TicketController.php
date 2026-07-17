<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\UserRole;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Models\Ticket;
use App\Http\Resources\Ticket\TicketResource;
use App\Http\Resources\Ticket\TicketResourceDetail;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $tickets = $this->service->getAll(
            filters: $request->only([
                'status',
                'priority',
                'category',
                'assigned_team',
                'reporter_id',
                'sla_breached',
                'overdue',
                'search',
                'is_public_submission',
            ]),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::paginated(
            $tickets,
            TicketResource::collection($tickets),
            'Tickets retrieved successfully.',
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->service->store($request->validated());

        return ApiResponse::success(
            new TicketResourceDetail($ticket),
            'Ticket created successfully.',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket): JsonResponse
    {
        $user = Auth::user();

        if ($user->role === UserRole::Reporter && $ticket->reporter_id !== $user->id) {
            abort(403, 'You are not authorized to view this ticket.');
        }

        return ApiResponse::success(
            new TicketResourceDetail($ticket->load(['reporter', 'assignedUser'])),
            'Ticket retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $updated = $this->service->update($ticket, $request->validated());

        return ApiResponse::success(
            new TicketResourceDetail($updated),
            'Ticket updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket): JsonResponse
    {
        $this->service->delete($ticket);

        return ApiResponse::success(
            null,
            'Ticket deleted successfully'
        );
    }
}

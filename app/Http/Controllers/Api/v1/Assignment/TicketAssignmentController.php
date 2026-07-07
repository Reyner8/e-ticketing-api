<?php

namespace App\Http\Controllers\Api\v1\Assignment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\AssignTeamRequest;
use App\Http\Requests\Ticket\AssignUserRequest;
use App\Models\Ticket;
use App\Services\Ticket\AssignmentService;
use App\Traits\HandleAssignment;
use Illuminate\Http\JsonResponse;

class TicketAssignmentController extends Controller
{
    use HandleAssignment {
        assignUser as protected traitAssignUser;
        assignTeam as protected traitAssignTeam;
        unassignUser as protected traitUnassignUser;
        unassignTeam as protected traitUnassignTeam;
    }

    public function __construct(
        protected AssignmentService $assignmentService
    ) {}

    protected function getAssignmentService(): AssignmentService
    {
        return $this->assignmentService;
    }

    public function assignUser(AssignUserRequest $request, Ticket $ticket): JsonResponse
    {
        return $this->traitAssignUser($request, $ticket);
    }

    public function assignTeam(AssignTeamRequest $request, Ticket $ticket): JsonResponse
    {
        return $this->traitAssignTeam($request, $ticket);
    }

    public function unassignUser(Ticket $ticket): JsonResponse
    {
        return $this->traitUnassignUser($ticket);
    }

    public function unassignTeam(Ticket $ticket): JsonResponse
    {
        return $this->traitUnassignTeam($ticket);
    }
}

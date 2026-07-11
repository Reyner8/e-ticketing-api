<?php

namespace App\Http\Controllers\Api\v1\Assignment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\AssignTeamRequest;
use App\Http\Requests\Ticket\AssignUserRequest;
use App\Models\ErrorReport;
use App\Services\Ticket\AssignmentService;
use App\Traits\HandleAssignment;
use Illuminate\Http\JsonResponse;

class ErrorReportAssignmentController extends Controller
{
    use HandleAssignment {
        assignUser as protected traitAssignUser;
        assignTeam as protected traitAssignTeam;
        unassignUser as protected traitUnassignUser;
        unassignTeam as protected traitUnassignTeam;
        claim as protected traitClaim;
    }

    public function __construct(
        protected AssignmentService $assignmentService
    ) {}

    protected function getAssignmentService(): AssignmentService
    {
        return $this->assignmentService;
    }

    public function assignUser(AssignUserRequest $request, ErrorReport $error): JsonResponse
    {
        return $this->traitAssignUser($request, $error);
    }

    public function assignTeam(AssignTeamRequest $request, ErrorReport $error): JsonResponse
    {
        return $this->traitAssignTeam($request, $error);
    }

    public function claim(ErrorReport $error): JsonResponse
    {
        return $this->traitClaim($error);
    }
    
    public function unassignUser(ErrorReport $error): JsonResponse
    {
        return $this->traitUnassignUser($error);
    }

    public function unassignTeam(ErrorReport $error): JsonResponse
    {
        return $this->traitUnassignTeam($error);
    }
}

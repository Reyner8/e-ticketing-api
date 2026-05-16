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
    use HandleAssignment;

    public function __construct(
        protected AssignmentService $assignmentService
    ) {}

    protected function getAssignmentService(): AssignmentService
    {
        return $this->assignmentService;
    }

    public function assignUser(AssignUserRequest $request, ErrorReport $error): JsonResponse
    {
        return $this->assignUser($request, $error);
    }

    public function assignTeam(AssignTeamRequest $request, ErrorReport $error): JsonResponse
    {
        return $this->assignTeam($request, $error);
    }
    
    public function unassignUser(ErrorReport $error): JsonResponse
    {
        return $this->unassignUser($error);
    }

    public function unassignTeam(ErrorReport $error): JsonResponse
    {
        return $this->unassignTeam($error);
    }
}

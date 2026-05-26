<?php

namespace App\Http\Controllers\Api\v1\Assignment;

use App\Http\Controllers\Controller;
use App\Services\Ticket\AssignmentService;
use App\Traits\HandleAssignment;
use App\Http\Requests\Ticket\AssignUserRequest;
use App\Http\Requests\Ticket\AssignTeamRequest;
use App\Models\FeatureRequest;
use Illuminate\Http\JsonResponse;

class FeatureRequestAssignmentController extends Controller
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

    public function assignUser(AssignUserRequest $request, FeatureRequest $feature): JsonResponse
    {
        return $this->traitAssignUser($request, $feature);
    }

    public function assignTeam(AssignTeamRequest $request, FeatureRequest $feature): JsonResponse
    {
        return $this->traitAssignTeam($request, $feature);
    }
    
    public function unassignUser(FeatureRequest $feature): JsonResponse
    {
        return $this->traitUnassignUser($feature);
    }

    public function unassignTeam(FeatureRequest $feature): JsonResponse
    {
        return $this->traitUnassignTeam($feature);
    }
}

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
    use HandleAssignment;

    public function __construct(
        protected AssignmentService $assignmentService
    ) {}

    protected function getAssignmentService(): AssignmentService
    {
        return $this->assignmentService;
    }

    public function assignUser(AssignUserRequest $request, FeatureRequest $feature): JsonResponse
    {
        return $this->assignUser($request, $feature);
    }

    public function assignTeam(AssignTeamRequest $request, FeatureRequest $feature): JsonResponse
    {
        return $this->assignTeam($request, $feature);
    }
    
    public function unassignUser(FeatureRequest $feature): JsonResponse
    {
        return $this->unassignUser($feature);
    }

    public function unassignTeam(FeatureRequest $feature): JsonResponse
    {
        return $this->unassignTeam($feature);
    }
}

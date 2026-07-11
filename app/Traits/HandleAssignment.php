<?php

namespace App\Traits;

use App\Helpers\ApiResponse;
use App\Http\Requests\Ticket\AssignTeamRequest;
use App\Http\Requests\Ticket\AssignUserRequest;
use App\Http\Resources\AssignmentResource;
use App\Services\Ticket\AssignmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

trait HandleAssignment
{
    abstract protected function getAssignmentService(): AssignmentService;

    public function assignUser(AssignUserRequest $request, Model $resource): JsonResponse
    {
        $updated = $this->getAssignmentService()->assignToUser(
            resource: $resource,
            userId: $request->validated('user_id')
        );

        return ApiResponse::success(
            new AssignmentResource($updated),
            'Resource assigned to user successfully'
        );
    }

    public function claim(Model $resource): JsonResponse
    {
        $updated = $this->getAssignmentService()->claim($resource);

        return ApiResponse::success(
            new AssignmentResource($updated),
            'Resource claimed successfully'
        );
    }

    public function assignTeam(AssignTeamRequest $request, Model $resource): JsonResponse
    {
        $updated = $this->getAssignmentService()->assignToTeam(
            resource: $resource,
            team: $request->validated('team')
        );

        return ApiResponse::success(
            new AssignmentResource($updated),
            'Resource assigned to team successfully'
        );
    }

    public function unassignUser(Model $resource): JsonResponse
    {
        $updated = $this->getAssignmentService()->unassignUser($resource);

        return ApiResponse::success(
            new AssignmentResource($updated),
            'User assignment removed successfully'
        );
    }

    public function unassignTeam(Model $resource): JsonResponse
    {
        $updated = $this->getAssignmentService()->unassignTeam($resource);

        return ApiResponse::success(
            new AssignmentResource($updated),
            'Team assignment removed successfully'
        );
    }
}
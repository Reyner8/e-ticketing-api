<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        protected readonly NotificationService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $notifications = $this->service->getForUser(
            userId: Auth::id(),
            filters: $request->only(['is_read', 'type', 'priority']),
            perPage: $request->integer('per_page', 15)
        );

        return ApiResponse::success(
            NotificationResource::collection($notifications),
            'Notifications retrieved successfully.'
        );
    }

    public function unreadCount(): JsonResponse
    {
        return ApiResponse::success(
            ['count' => $this->service->unreadCount(Auth::id())],
            'Unread count retrieved successfully.'
        );
    }

    public function show(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'This notification does not belong to you.');
        }

        return ApiResponse::success(
            new NotificationResource($notification->load(['ticket', 'downtime'])),
            'Notification retrieved successfully.'
        );
    }

    public function markAsRead(Notification $notification): JsonResponse
    {
        $updated = $this->service->markAsRead(
            notification: $notification,
            userId: Auth::id()
        );

        return ApiResponse::success(
            new NotificationResource($updated),
            'Notification marked as read.'
        );
    }

    public function markAllAsRead(): JsonResponse
    {
        $count = $this->service->markAllAsRead(
            userId: Auth::id()
        );

        return ApiResponse::success(
            ['updated_count' => $count],
            "{$count} notification(s) marked as read."
        );
    }

    public function markManyAsRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notification_ids' => ['required', 'array', 'min:1'],
            'notification_ids.*' => ['integer'],
        ]);

        $count = $this->service->markManyAsRead(
            userId: Auth::id(),
            notificationIds: $validated['notification_ids']
        );

        return ApiResponse::success(
            ['updated_count' => $count],
            "{$count} notification(s) marked as read."
        );
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $this->service->delete(
            notification: $notification,
            userId: Auth::id()
        );

        return ApiResponse::success(
            null,
            'Notification deleted successfully.'
        );
    }

    public function deleteAllRead(): JsonResponse
    {
        $count = $this->service->deleteAllRead(Auth::id());

        return ApiResponse::success(
            ['deleted_count' => $count],
            "{$count} notification(s) deleted."
        );
    }
}

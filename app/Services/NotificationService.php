<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Enums\Priorities;
use App\Models\DowntimeRecord;
use App\Models\Notification;
use App\Models\Ticket;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function create(
        int $userId,
        NotificationType $type,
        string $title,
        string $message,
        Priorities $priority,
        ?string $actionUrl = null,
        ?string $ticketId = null,
        ?int $downtimeId =null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type->value,
            'title' => $title,
            'message' => $message,
            'priority' => $priority->value,
            'action_url' => $actionUrl,
            'ticket_id' => $ticketId,
            'downtime_id' => $downtimeId,
            'is_read' => false
        ]);
    }

    // Shorthand
    public function notifyTicketAssigned(int $userId, Ticket $ticket): Notification
    {
        return $this->create(
            userId: $userId,
            type: NotificationType::TicketAssigned,
            title: 'Ticket Assigned to You',
            message: "Ticket {$ticket->title} has been assigned to you.",
            priority: Priorities::High,
            actionUrl: "/tickets/{$ticket->id}",
            ticketId: $ticket->id
        );
    }

    public function notifyTicketUpdated(int $userId, Ticket $ticket, string $updateDetails): Notification
    {
        return $this->create(
            userId: $userId,
            type: NotificationType::TicketUpdated,
            title: 'Ticket Updated',
            message: "Ticket {$ticket->title} has been updated. {$updateDetails}.",
            priority: Priorities::Medium,
            actionUrl: "/tickets/{$ticket->id}",
            ticketId: $ticket->id
        );
    }

    public function notifySlaBreached(int $userId, Ticket $ticket): Notification
    {
        return $this->create(
            userId: $userId,
            type: NotificationType::SlaBreached,
            title: 'SLA Breach Alert',
            message: "Ticket {$ticket->title} has breached its SLA.",
            priority: Priorities::High,
            actionUrl: "/tickets/{$ticket->id}",
            ticketId: $ticket->id,
        );
    }

    public function notifyDowntimeAlert(int $userId, DowntimeRecord $downtime): Notification
    {
        return $this->create(
            userId: $userId,
            type: NotificationType::DowntimeAlert,
            title: 'Downtime Alert',
            message: "Downtime reported: {$downtime->title}. {$downtime->impact->label()}.",
            priority: Priorities::High,
            actionUrl: "/downtime-records/{$downtime->id}",
            downtimeId: $downtime->id
        );
    }

    public function notifyMaintenanceReminder(int $userId, string $details): Notification
    {
        return $this->create(
            userId: $userId,
            type: NotificationType::MaintenanceReminder,
            title: 'Maintenance Reminder',
            message: $details,
            priority: Priorities::Medium
        );
    }

    public function notifyCommentMention(int $userId, Ticket $ticket, string $mentionedBy): Notification
    {
        return $this->create(
            userId: $userId,
            type: NotificationType::CommentMention,
            title: 'You Were Mentioned',
            message: "{$mentionedBy} mentioned you in a comment on ticket '{$ticket->title}'.",
            priority: Priorities::Medium,
            actionUrl: "/tickets/{$ticket->id}",
            ticketId: $ticket->id
        );
    }

    public function notifyTicketConverted(int $userId, Ticket $ticket, string $toType): Notification
    {
        return $this->create(
            userId: $userId,
            type: NotificationType::TicketConverted,
            title: 'Ticket Converted',
            message: "Ticket {$ticket->title} has been converted to {$toType}.",
            priority: Priorities::Medium,
            actionUrl: "/tickets/{$ticket->id}",
            ticketId: $ticket->id,
        );
    }

    //* Query
    public function getForUser(
        int $userId,
        array $filters = [],
        int $perPage = 15
    ): LengthAwarePaginator {
        return Notification::forUser($userId)
        ->when(
            isset($filters['is_read']),
            fn ($q) => $q->where(
                'is_read',
                filter_var($filters['is_read'], FILTER_VALIDATE_BOOLEAN)
            )
        )
        ->when(
            isset($filters['type']),
            fn ($q) => $q->byType($filters['type'])
        )
        ->when(
            isset($filters['priority']),
            fn ($q) => $q->byPriority($filters['priority'])
        )
        ->latest('created_at')
        ->paginate(min($perPage, 50));
    }

    public function unreadCount(int $userId): int
    {
        return Notification::forUser($userId)->unread()->count();
    }

    public function markAsRead(Notification $notification, int $userId): Notification
    {
        if ($notification->user_id !== $userId) {
            abort(403, 'This notification does not belong to you.');
        }

        if (! $notification->is_read) {
            $notification->update(['is_read' => true]);
        }

        return $notification;
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::forUser($userId)
        ->unread()
        ->update(['is_read' => true]);
    }

    public function markManyAsRead(int $userId, array $notificationIds): int
    {
        return Notification::forUser($userId)
        ->whereIn('id', $notificationIds)
        ->unread()
        ->update(['is_read' => true]);
    }

    public function delete(Notification $notification, int $userId): void
    {
        if ($notification->user_id !== $userId) {
            abort(403, 'This notification does not belong to you.');
        }

        $notification->delete();
    }

    public function deleteAllRead(int $userId): int
    {
        return Notification::forUser($userId)->read()->delete();
    }
}

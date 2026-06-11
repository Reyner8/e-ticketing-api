<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly Ticket $ticket,
        private readonly string $event,
        private readonly array $details = [],
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->resolveSubject();

        return (new MailMessage)
            ->subject($subject)
            ->line("Ticket '{$this->ticket->title}' has been updated.")
            ->line("Event: {$this->event}")
            ->action('View Ticket', url("/tickets/{$this->ticket->id}"));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'event' => $this->event,
            'details' => $this->details,
        ];
    }

    // Private
    private function resolveSubject(): string
    {
        return match ($this->event) {
            'status_changed' => "Ticket Status Updated: {$this->ticket->title}",
            'commented' => "New Comment On: {$this->ticket->title}",
            'assigned' => "Ticket Assigned: {$this->ticket->title}",
            'attachment_added' => "New Attachment On: {$this->ticket->title}",
            default => "Ticket Updated: {$this->ticket->title}",
        }; 
    }
}

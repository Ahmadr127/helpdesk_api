<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketUpdatedNotification extends Notification
{
    use Queueable;

    protected $ticket;
    protected $action;

    public function __construct($ticket, $action)
    {
        $this->ticket = $ticket;
        $this->action = $action;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => "Ticket Updated",
            'message' => "Your ticket #{$this->ticket->ticket_number} has been {$this->action}",
            'action' => $this->action
        ];
    }
} 
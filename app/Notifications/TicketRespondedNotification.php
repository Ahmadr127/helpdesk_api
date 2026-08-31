<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketRespondedNotification extends Notification
{
    use Queueable;

    protected $ticket;
    protected $responder;
    protected $message;
    protected $isAdmin;
    protected $action;

    public function __construct($ticket, $responder, $message, $isAdmin = false, $action = 'responded')
    {
        $this->ticket = $ticket;
        $this->responder = $responder;
        $this->message = $message;
        $this->isAdmin = $isAdmin;
        $this->action = $action;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $title = "Ticket {$this->action}";
        if ($this->isAdmin) {
            $title = "Admin {$this->action} to your ticket";
        }

        return [
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => $title,
            'message' => $this->message,
            'responder_name' => $this->responder->name,
            'responder_role' => $this->isAdmin ? 'Admin' : 'User',
            'url' => $this->isAdmin ? 
                route('admin.tickets.show', $this->ticket) : 
                route('user.ticket.show', $this->ticket),
            'color' => $this->isAdmin ? 'blue' : 'green',
            'timestamp' => now()->format('d M Y H:i'),
            'ticket_status' => $this->ticket->status,
            'ticket_priority' => $this->ticket->priority,
            'ticket_category' => $this->ticket->category
        ];
    }
} 
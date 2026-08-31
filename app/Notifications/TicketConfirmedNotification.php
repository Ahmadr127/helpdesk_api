<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketConfirmedNotification extends Notification
{
    use Queueable;

    protected $ticket;
    protected $notes;
    protected $photo;

    public function __construct($ticket, $notes, $photo = null)
    {
        $this->ticket = $ticket;
        $this->notes = $notes;
        $this->photo = $photo;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => "Ticket Confirmed",
            'message' => "Ticket #{$this->ticket->ticket_number} has been confirmed as completed",
            'notes' => $this->notes,
            'photo' => $this->photo,
            'url' => route('admin.tickets.show', $this->ticket),
            'color' => 'green',
            'user_name' => $this->ticket->user->name,
            'category_name' => $this->ticket->categoryRelation ? $this->ticket->categoryRelation->name : $this->ticket->category,
            'department_name' => $this->ticket->departmentRelation ? $this->ticket->departmentRelation->name : $this->ticket->department,
            'building_name' => $this->ticket->buildingRelation ? $this->ticket->buildingRelation->name : $this->ticket->building,
            'confirmation_time' => now()->format('d M Y H:i')
        ];
    }
} 
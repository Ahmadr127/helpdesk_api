<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketRejectedNotification extends Notification
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
            'title' => "Ticket Rejected",
            'message' => "Ticket #{$this->ticket->ticket_number} has been rejected by user",
            'notes' => $this->notes,
            'photo' => $this->photo,
            'url' => route('admin.tickets.show', $this->ticket),
            'color' => 'red',
            'user_name' => $this->ticket->user->name,
            'category_name' => $this->ticket->categoryRelation ? $this->ticket->categoryRelation->name : $this->ticket->category,
            'department_name' => $this->ticket->departmentRelation ? $this->ticket->departmentRelation->name : $this->ticket->department,
            'building_name' => $this->ticket->buildingRelation ? $this->ticket->buildingRelation->name : $this->ticket->building,
            'rejection_time' => now()->format('d M Y H:i')
        ];
    }
} 
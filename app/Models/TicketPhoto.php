<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TicketPhoto extends Model
{
    protected $fillable = [
        'ticket_id',
        'photo_path',
        'type'
    ];

    protected $appends = ['url'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function getUrlAttribute()
    {
        if (!$this->photo_path) {
            return null;
        }
        return Storage::disk('public')->url($this->photo_path);
    }
} 
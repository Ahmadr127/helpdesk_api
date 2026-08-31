<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'read_expiry_days',
        'unread_expiry_days',
        'auto_delete_read',
        'auto_delete_unread',
    ];

    protected $casts = [
        'auto_delete_read' => 'boolean',
        'auto_delete_unread' => 'boolean',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }
} 
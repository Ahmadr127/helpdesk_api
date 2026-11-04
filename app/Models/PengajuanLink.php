<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanLink extends Model
{
    protected $fillable = [
        'ticket_id',
        'order_perbaikan_id',
        'kategori_name',
        'sync_enabled',
        'created_by',
    ];

    protected $casts = [
        'sync_enabled' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function orderPerbaikan()
    {
        return $this->belongsTo(OrderPerbaikan::class, 'order_perbaikan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

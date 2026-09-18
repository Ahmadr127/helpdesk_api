<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPerbaikanHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_perbaikan_id',
        'status',
        'follow_up',
        'keterangan',
        'lampiran',
        'created_by',
    ];

    public function order()
    {
        return $this->belongsTo(OrderPerbaikan::class, 'order_perbaikan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            OrderPerbaikan::STATUS_OPEN => 'Open',
            OrderPerbaikan::STATUS_IN_PROGRESS => 'In Progress',
            OrderPerbaikan::STATUS_TUTUP => 'Tutup',
            OrderPerbaikan::STATUS_CONFIRMED => 'Confirmed',
            OrderPerbaikan::STATUS_REJECTED => 'Rejected',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            OrderPerbaikan::STATUS_OPEN => 'bg-blue-100 text-blue-800',
            OrderPerbaikan::STATUS_IN_PROGRESS => 'bg-yellow-100 text-yellow-800',
            OrderPerbaikan::STATUS_TUTUP => 'bg-violet-100 text-violet-800',
            OrderPerbaikan::STATUS_CONFIRMED => 'bg-green-100 text-green-800',
            OrderPerbaikan::STATUS_REJECTED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}

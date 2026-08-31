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
} 
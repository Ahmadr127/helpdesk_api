<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPerbaikan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'order_perbaikan';

    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'nomor',
        'tanggal',
        'unit_proses',
        'unit_proses_name',
        'unit_penerima',
        'nama_peminta',
        'jenis_barang',
        'kode_inventaris',
        'nama_barang',
        'lokasi',
        'keluhan',
        'prioritas',
        'status',
        'follow_up',
        'nama_penanggung_jawab',
        'foto',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'tanggal' => 'datetime:Y-m-d H:i:s',
    ];

    // Relationships
    public function history()
    {
        return $this->hasMany(OrderPerbaikanHistory::class, 'order_perbaikan_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'lokasi');
    }

    // Status helper methods
    public function isOpen()
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isConfirmed()
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isRejected()
    {
        return $this->status === self::STATUS_REJECTED;
    }

    // Get status badge color
    public function getStatusBadgeClass()
    {
        return match($this->status) {
            self::STATUS_OPEN => 'bg-blue-100 text-blue-800',
            self::STATUS_IN_PROGRESS => 'bg-yellow-100 text-yellow-800',
            self::STATUS_CONFIRMED => 'bg-green-100 text-green-800',
            self::STATUS_REJECTED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    // Get status badge dot color
    public function getStatusDotClass()
    {
        return match($this->status) {
            self::STATUS_OPEN => 'bg-blue-400',
            self::STATUS_IN_PROGRESS => 'bg-yellow-400',
            self::STATUS_CONFIRMED => 'bg-green-400',
            self::STATUS_REJECTED => 'bg-red-400',
            default => 'bg-gray-400',
        };
    }

    // Get formatted status text
    public function getStatusText()
    {
        return match($this->status) {
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Unknown'
        };
    }
} 
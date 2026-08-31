<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor',
        'tanggal',
        'unit_proses_code',
        'unit_proses_name',
        'nip_peminta',
        'prioritas',
        'jenis_barang',
        'kode_inventaris',
        'nama_barang',
        'deskripsi_kerusakan',
        'status',
        'admin_notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'open',
    ];

    public function photos(): HasMany
    {
        return $this->hasMany(OrderPhoto::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nip_peminta', 'nip');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'open' => 'yellow',
            'in_progress' => 'blue',
            'confirmed' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'confirmed' => 'Confirmed',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    public function getPrioritasColorAttribute(): string
    {
        return match($this->prioritas) {
            'URGENT' => 'red',
            'SEGERA' => 'yellow',
            'BIASA' => 'gray',
            default => 'gray',
        };
    }

    public function getPrioritasLabelAttribute(): string
    {
        return match($this->prioritas) {
            'URGENT' => 'URGENT',
            'SEGERA' => 'SEGERA',
            'BIASA' => 'BIASA',
            default => 'Unknown',
        };
    }
} 
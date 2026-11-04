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

    const STATUS_TUTUP = 'tutup';

    const STATUS_CONFIRMED = 'confirmed';

    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'nomor',
        'tanggal',
        'department_id',
        'nama_peminta',
        'kategori_order',
        'lokasi',
        'gedung',
        'keluhan',
        'prioritas',
        'status',
        'follow_up',
        'foto',
        'created_by',
        'updated_by',
        'closed_at',
    ];

    protected $casts = [
        'tanggal' => 'datetime:Y-m-d H:i:s',
        'foto' => 'array',
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

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'lokasi');
    }

    public function penanggungJawab()
    {
        return $this->belongsToMany(User::class, 'order_perbaikan_penanggung_jawab', 'order_perbaikan_id', 'user_id')->withTimestamps();
    }

    /**
     * Referensi kategori order (kolom string `kategori_order` = nama).
     * Dipakai untuk visibilitas berbasis assignment/PJ.
     */
    public function kategoriOrderRef()
    {
        return $this->belongsTo(KategoriOrder::class, 'kategori_order', 'name');
    }

    public function getNamaPenanggungJawabAttribute()
    {
        if (! $this->relationLoaded('penanggungJawab')) {
            $this->load('penanggungJawab');
        }

        return $this->penanggungJawab->pluck('name')->implode(', ');
    }

    /**
     * Daftar path foto order sebagai array (dukung data lama berupa string).
     */
    public function getFotoPathsAttribute(): array
    {
        $foto = $this->foto;
        if (is_array($foto)) {
            return array_values(array_filter($foto));
        }

        return $foto ? [$foto] : [];
    }

    /**
     * Daftar URL publik foto order.
     */
    public function getFotoUrlsAttribute(): array
    {
        return array_map(fn ($p) => \Illuminate\Support\Facades\Storage::disk('public')->url($p), $this->getFotoPathsAttribute());
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

    public function isTutup()
    {
        return $this->status === self::STATUS_TUTUP;
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
        return match ($this->status) {
            self::STATUS_OPEN => 'bg-blue-100 text-blue-800',
            self::STATUS_IN_PROGRESS => 'bg-yellow-100 text-yellow-800',
            self::STATUS_TUTUP => 'bg-violet-100 text-violet-800',
            self::STATUS_CONFIRMED => 'bg-green-100 text-green-800',
            self::STATUS_REJECTED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    // Get status badge dot color
    public function getStatusDotClass()
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'bg-blue-400',
            self::STATUS_IN_PROGRESS => 'bg-yellow-400',
            self::STATUS_TUTUP => 'bg-violet-400',
            self::STATUS_CONFIRMED => 'bg-green-400',
            self::STATUS_REJECTED => 'bg-red-400',
            default => 'bg-gray-400',
        };
    }

    // Get formatted status text
    public function getStatusText()
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'Open',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_TUTUP => 'Tutup',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Unknown'
        };
    }

    // Status color name untuk badge header (dipakai view admin.show)
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'blue',
            self::STATUS_IN_PROGRESS => 'yellow',
            self::STATUS_TUTUP => 'violet',
            self::STATUS_CONFIRMED => 'green',
            self::STATUS_REJECTED => 'red',
            default => 'gray',
        };
    }

    // Status label untuk tampilan
    public function getStatusLabelAttribute()
    {
        return $this->getStatusText();
    }
}

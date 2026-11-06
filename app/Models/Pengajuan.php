<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pengajuan extends Model
{
    protected $table = 'pengajuan';
    use SoftDeletes;

    protected $fillable = [
        'nomor',
        'kategori_id',
        'pemohon_id',
        'pemohon_nama',
        'department_id',
        'lokasi_id',
        'building_id',
        'deskripsi',
        'prioritas',
        'status',
        'follow_up',
        'tanggal',
        'started_at',
        'closed_at',
        'confirmed_at',
        'rejected_at',
        'rejection_count',
        'user_confirmation',
        'user_confirmed_at',
        'created_by',
        'updated_by',
        'legacy_source',
        'legacy_id',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'user_confirmation' => 'boolean',
        'user_confirmed_at' => 'datetime',
        'rejection_count' => 'integer',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriPengajuan::class, 'kategori_id');
    }

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemohon_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'lokasi_id');
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function orderDetail(): HasOne
    {
        return $this->hasOne(PengajuanOrderDetail::class, 'pengajuan_id');
    }

    public function historis(): HasMany
    {
        return $this->hasMany(PengajuanHistori::class, 'pengajuan_id');
    }

    public function pesans(): HasMany
    {
        return $this->hasMany(PengajuanPesan::class, 'pengajuan_id');
    }

    public function lampirans(): HasMany
    {
        return $this->hasMany(PengajuanLampiran::class, 'pengajuan_id');
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(PengajuanPenugasan::class, 'pengajuan_id');
    }

    public function legacyMap(): HasOne
    {
        return $this->hasOne(LegacyMap::class, 'pengajuan_id');
    }

    // Backward-compat accessors for legacy views
    public function getTicketNumberAttribute(): string
    {
        return (string) $this->nomor;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->deskripsi;
    }

    public function getPriorityAttribute(): ?string
    {
        return $this->prioritas;
    }

    public function getUserIdAttribute(): ?int
    {
        return $this->pemohon_id;
    }

    // Backward-compat: old Ticket/OrderPerbaikan stored these as strings.
    // Return the related model's name so views/exports keep working without per-view changes.
    public function getDepartmentAttribute()
    {
        return $this->relations['department']?->name ?? $this->department_id ? \App\Models\Department::find($this->department_id)?->name : null;
    }

    public function getBuildingAttribute()
    {
        return $this->relations['building']?->name ?? ($this->building_id ? \App\Models\Building::find($this->building_id)?->name : null);
    }

    public function getLocationAttribute()
    {
        return $this->lokasi?->name;
    }

    public function getUserAttribute()
    {
        return $this->pemohon;
    }

    public function getCategoryAttribute(): ?string
    {
        return $this->kategori?->name;
    }

    public function getPhotoAttribute(): ?string
    {
        return $this->lampirans->first()?->path;
    }

    public function getPhotosAttribute()
    {
        return $this->lampirans;
    }

    public function getUnitPenerimaAttribute(): ?string
    {
        return $this->orderDetail?->unit_penerima;
    }

    public function getKodeInventarisAttribute(): ?string
    {
        return $this->orderDetail?->kode_inventaris;
    }

    public function getNamaBarangAttribute(): ?string
    {
        return $this->orderDetail?->nama_barang;
    }

    public function getUnitProsesIdAttribute(): ?int
    {
        return $this->orderDetail?->unit_proses_id;
    }

    public function getKeluhanAttribute(): ?string
    {
        return $this->deskripsi;
    }
}

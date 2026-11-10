<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriPengajuan extends Model
{
    protected $table = 'kategori_pengajuan';
    use SoftDeletes;

    protected $fillable = ['code', 'name', 'description', 'status'];

    protected $casts = [
        'status' => 'integer',
    ];

    /** BC accessor: jenis pertama dari pivot (menggantikan kolom single jenis_id). */
    public function getJenisAttribute(): ?JenisPengajuan
    {
        if ($this->relationLoaded('jenisList')) {
            return $this->jenisList->first();
        }

        return $this->jenisList()->first();
    }

    /** BC accessor: ID jenis pertama dari pivot (menggantikan kolom single jenis_id). */
    public function getJenisIdAttribute(): ?int
    {
        $jenis = $this->jenis;

        return $jenis?->id;
    }

    /** Jenis-jenis yang terhubung ke kategori ini (banyak-banyak). */
    public function jenisList(): BelongsToMany
    {
        return $this->belongsToMany(JenisPengajuan::class, 'kategori_jenis', 'kategori_id', 'jenis_id')
            ->withTimestamps()
            ->orderBy('code');
    }

    /** Ganti total daftar jenis untuk kategori ini. */
    public function syncJenis(array $jenisIds): self
    {
        $ids = array_values(array_unique(array_map('intval', $jenisIds)));

        $this->jenisList()->sync($ids);

        return $this;
    }

    /** Buat kode jenis gabungan, e.g. "IT/IPSRS" atau "IT". */
    public function getJenisDisplayAttribute(): string
    {
        $codes = $this->getJenisCodesAttribute();

        return $codes !== [] ? implode('/', $codes) : '-';
    }

    /** Daftar kode jenis yang terhubung. */
    public function getJenisCodesAttribute(): array
    {
        $codes = $this->relationLoaded('jenisList')
            ? $this->jenisList->pluck('code')
            : ($this->exists ? $this->jenisList()->pluck('code') : collect());

        return $codes->values()->all();
    }

    /** Apakah kategori ini terhubung ke jenis tertentu. */
    public function hasJenis(string $code): bool
    {
        return in_array($code, $this->getJenisCodesAttribute(), true);
    }

    /** Buat nama jenis gabungan, e.g. "Tiket IT / SIRS, Order Perbaikan / IPSRS". */
    public function getJenisNamesAttribute(): string
    {
        $names = $this->relationLoaded('jenisList')
            ? $this->jenisList
            : $this->jenisList()->get();

        return $names->pluck('name')->sort()->implode(', ') ?: '-';
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_kategori', 'kategori_id', 'role_id')
            ->withTimestamps();
    }

    public function roleKategoris(): HasMany
    {
        return $this->hasMany(RoleKategori::class, 'kategori_id');
    }

    /** Kategori hanya untuk flavour SIRS (kode IT). */
    public function scopeSirs(Builder $query): Builder
    {
        return $query->whereHas('jenisList', fn (Builder $q) => $q->where('code', 'IT'));
    }

    /**
     * Kategori untuk flavour UMUM (selain SIRS) — apa pun jenis yang bukan SIRS.
     * Kategori multi-flavour (mis. SIRS + IPSRS) muncul di keduanya.
     */
    public function scopeUmum(Builder $query): Builder
    {
        return $query->whereHas('jenisList', fn (Builder $q) => $q->where('code', '!=', 'IT'));
    }

    public function pengajuans(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'kategori_id');
    }
}

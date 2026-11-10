<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisPengajuan extends Model
{
    protected $table = 'jenis_pengajuan';
    protected $fillable = ['code', 'name', 'prefix', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Kategori-kategori yang terhubung ke jenis ini (banyak-banyak). */
    public function kategoris(): BelongsToMany
    {
        return $this->belongsToMany(KategoriPengajuan::class, 'kategori_jenis', 'jenis_id', 'kategori_id')
            ->withTimestamps();
    }

    public function documentSequences(): HasMany
    {
        return $this->hasMany(DocumentSequence::class, 'jenis_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisPengajuan extends Model
{
    protected $table = 'jenis_pengajuan';
    protected $fillable = ['code', 'name', 'prefix', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function kategoris(): HasMany
    {
        return $this->hasMany(KategoriPengajuan::class, 'jenis_id');
    }

    public function documentSequences(): HasMany
    {
        return $this->hasMany(DocumentSequence::class, 'jenis_id');
    }
}

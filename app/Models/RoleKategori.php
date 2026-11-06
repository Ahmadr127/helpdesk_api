<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleKategori extends Model
{
    protected $table = 'role_kategori';

    protected $fillable = [
        'role_id',
        'kategori_id',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriPengajuan::class, 'kategori_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriPengajuan extends Model
{
    protected $table = 'kategori_pengajuan';
    use SoftDeletes;

    protected $fillable = ['jenis_id', 'code', 'name', 'description', 'status'];

    protected $casts = [
        'status' => 'integer',
    ];

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisPengajuan::class, 'jenis_id');
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

    public function pengajuans(): HasMany
    {
        return $this->hasMany(Pengajuan::class, 'kategori_id');
    }
}

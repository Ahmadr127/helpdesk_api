<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = ['slug', 'name', 'description', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role', 'slug');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
            ->withTimestamps();
    }

    public function roleKategoris(): HasMany
    {
        return $this->hasMany(RoleKategori::class, 'role_id');
    }

    public function kategoris(): BelongsToMany
    {
        return $this->belongsToMany(KategoriPengajuan::class, 'role_kategori', 'role_id', 'kategori_id')
            ->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'group', 'description', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'permission_user', 'permission_id', 'user_id')->withTimestamps();
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id')
            ->withTimestamps();
    }
}

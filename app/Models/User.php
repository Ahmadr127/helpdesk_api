<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'position',
        'role',
        'status',
        'password',
        'department',
        'department_id',
        'fcm_token',
        'fcm_token_updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'integer',
        'fcm_token_updated_at' => 'datetime',
    ];

    public function notificationSetting()
    {
        return $this->morphOne(NotificationSetting::class, 'notifiable');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function validDeviceTokens()
    {
        return $this->hasMany(DeviceToken::class)->where('is_valid', true);
    }

    /**
     * Get all FCM tokens for user (merge legacy fcm_token + device_tokens).
     */
    public function getAllFcmTokens(): array
    {
        $tokens = $this->validDeviceTokens()->pluck('token')->filter()->toArray();
        if (!empty($this->fcm_token) && !in_array($this->fcm_token, $tokens, true)) {
            $tokens[] = $this->fcm_token;
        }
        return array_values(array_filter($tokens));
    }

    public function scopeAdminIT($query)
    {
        return $query->where('role', 'admin')->whereRaw('LOWER(position) = ?', ['it']);
    }

    public function scopeAdminUmum($query)
    {
        return $query->where('role', 'admin')->whereRaw('LOWER(position) = ?', ['administrasi']);
    }

    // Permissions relationships & helpers
    public function permissions()
    {
        return $this->belongsToMany(\App\Models\Permission::class, 'permission_user', 'user_id', 'permission_id')->withTimestamps();
    }

    public function hasPermission(string $slug): bool
    {
        // Direct user permission
        if ($this->relationLoaded('permissions')) {
            if ($this->permissions->contains('slug', $slug)) return true;
        } else {
            if ($this->permissions()->where('slug', $slug)->exists()) return true;
        }
        // Role-based permission (via role_permissions table)
        $role = $this->role;
        $position = $this->position ? strtolower($this->position) : null;
        // Check exact role+position, then role wildcard
        $exists = \Illuminate\Support\Facades\DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('permissions.slug', $slug)
            ->where(function($q) use ($role, $position){
                $q->where(function($qq) use ($role, $position){
                    $qq->where('role_permissions.role', $role)
                       ->whereRaw('LOWER(role_permissions.position) = ?', [$position]);
                })->orWhere(function($qq) use ($role){
                    $qq->where('role_permissions.role', $role)
                       ->whereNull('role_permissions.position');
                });
            })->exists();
        if ($exists) return true;
        // Fallback: admin IT has all if no permission system seeded yet (graceful)
        if (\Illuminate\Support\Facades\Schema::hasTable('permissions') && \Illuminate\Support\Facades\DB::table('permissions')->count()===0) {
            return true;
        }
        return false;
    }

    public function hasAnyPermission(array $slugs): bool
    {
        foreach ($slugs as $s) if ($this->hasPermission($s)) return true;
        return false;
    }

    public function hasAllPermissions(array $slugs): bool
    {
        foreach ($slugs as $s) if (!$this->hasPermission($s)) return false;
        return true;
    }

    public function isAdminIT(): bool
    {
        return $this->role === 'admin' && strtolower($this->position ?? '') === 'it';
    }
    public function isAdminUmum(): bool
    {
        return $this->role === 'admin' && strtolower($this->position ?? '') === 'administrasi';
    }

    /**
     * Get identifier for login (username or email)
     */
    public function getUsernameAttributeForLogin(): string
    {
        return $this->username ?? $this->email;
    }
}
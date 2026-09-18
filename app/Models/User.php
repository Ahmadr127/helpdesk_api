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
        if (! empty($this->fcm_token) && ! in_array($this->fcm_token, $tokens, true)) {
            $tokens[] = $this->fcm_token;
        }

        return array_values(array_filter($tokens));
    }

    public function roleModel()
    {
        return $this->belongsTo(\App\Models\Role::class, 'role', 'slug');
    }

    public function hasRole(string ...$slugs): bool
    {
        return in_array($this->role, $slugs, true);
    }

    public function assignRole(string $slug): bool
    {
        if (! app(\App\Services\Access\RoleService::class)->exists($slug)) {
            return false;
        }

        return $this->update(['role' => $slug]);
    }

    /**
     * Admin IT = siapa pun yang memegang permission admin.
     * Tetap dukung role lama 'admin' agar query notifikasi tidak bocor
     * saat tabel permissions belum di-seed.
     */
    public function scopeAdminIT($query)
    {
        return $query->where(function ($q) {
            $q->where('role', 'admin')
                ->orWhereIn('id', function ($sub) {
                    $sub->select('permission_user.user_id')
                        ->from('permission_user')
                        ->join('permissions', 'permissions.id', '=', 'permission_user.permission_id')
                        ->whereIn('permissions.slug', ['admin.dashboard', 'ticket.manage']);
                })
                ->orWhereIn('role', function ($sub) {
                    $sub->select('role_permissions.role')
                        ->from('role_permissions')
                        ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                        ->whereIn('permissions.slug', ['admin.dashboard', 'ticket.manage']);
                });
        });
    }

    public function scopeAdminUmum($query)
    {
        return $query->where(function ($q) {
            $q->where('role', 'ipsrs')
                ->orWhereIn('id', function ($sub) {
                    $sub->select('permission_user.user_id')
                        ->from('permission_user')
                        ->join('permissions', 'permissions.id', '=', 'permission_user.permission_id')
                        ->whereIn('permissions.slug', ['ipsrs.dashboard', 'order.manage']);
                })
                ->orWhereIn('role', function ($sub) {
                    $sub->select('role_permissions.role')
                        ->from('role_permissions')
                        ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                        ->whereIn('permissions.slug', ['ipsrs.dashboard', 'order.manage']);
                });
        });
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
            if ($this->permissions->contains('slug', $slug)) {
                return true;
            }
        } else {
            if ($this->permissions()->where('slug', $slug)->exists()) {
                return true;
            }
        }
        // Role-based permission (via role_permissions table) - berbasis role saja
        $role = $this->role;
        $exists = \Illuminate\Support\Facades\DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('permissions.slug', $slug)
            ->where('role_permissions.role', $role)
            ->exists();
        if ($exists) {
            return true;
        }

        // Fail-closed: tanpa baris permission yang cocok, akses ditolak.
        // (Dulu fail-open saat tabel permissions kosong — itu yang membuat
        // user biasa lolos ke endpoint admin di test.)
        return false;
    }

    public function hasAnyPermission(array $slugs): bool
    {
        foreach ($slugs as $s) {
            if ($this->hasPermission($s)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions(array $slugs): bool
    {
        foreach ($slugs as $s) {
            if (! $this->hasPermission($s)) {
                return false;
            }
        }

        return true;
    }

    public function isAdminIT(): bool
    {
        return $this->hasRole('admin')
            || $this->hasAnyPermission(['admin.dashboard', 'ticket.manage']);
    }

    public function isAdminUmum(): bool
    {
        return $this->hasRole('ipsrs')
            || $this->hasAnyPermission(['ipsrs.dashboard', 'order.manage']);
    }

    /**
     * Get identifier for login (username or email)
     */
    public function getUsernameAttributeForLogin(): string
    {
        return $this->username ?? $this->email;
    }
}

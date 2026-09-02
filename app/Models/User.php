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
        'email',
        'phone',
        'position',
        'role',
        'status',
        'password',
        'department',
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
}
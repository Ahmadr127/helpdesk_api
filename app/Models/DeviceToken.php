<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'is_valid',
        'last_used_at',
    ];

    protected $casts = [
        'is_valid' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    public static function upsertToken(int $userId, string $token, ?string $platform = null): self
    {
        $record = self::where('token', $token)->first();

        if ($record) {
            $record->update([
                'user_id' => $userId,
                'platform' => $platform ?? $record->platform,
                'is_valid' => true,
                'last_used_at' => now(),
            ]);
        } else {
            $record = self::create([
                'user_id' => $userId,
                'token' => $token,
                'platform' => $platform,
                'is_valid' => true,
                'last_used_at' => now(),
            ]);
        }

        // Token baru = perangkat yang sama registrasi ulang (rotasi FCM /
        // reinstall / login ulang). Tandai token lama user+platform yang sama
        // sebagai tidak valid agar 1 event tidak terkirim berkali-kali ke
        // perangkat yang sama. Hanya bila platform diketahui agar tidak
        // mematikan perangkat lain milik user yang sama.
        if ($platform) {
            self::where('user_id', $userId)
                ->where('platform', $platform)
                ->where('id', '!=', $record->id)
                ->where('is_valid', true)
                ->update(['is_valid' => false]);
        }

        return $record;
    }
}

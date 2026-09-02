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
            return $record;
        }

        return self::create([
            'user_id' => $userId,
            'token' => $token,
            'platform' => $platform,
            'is_valid' => true,
            'last_used_at' => now(),
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengajuanPesan extends Model
{
    protected $table = 'pengajuan_pesan';

    protected $fillable = [
        'pengajuan_id',
        'sender_id',
        'sender_type',
        'body',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function lampirans(): HasMany
    {
        return $this->hasMany(PengajuanLampiran::class, 'pesan_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengajuanHistori extends Model
{
    protected $table = 'pengajuan_histori';

    protected $fillable = [
        'pengajuan_id',
        'status',
        'action',
        'notes',
        'actor_id',
        'actor_type',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function lampirans(): HasMany
    {
        return $this->hasMany(PengajuanLampiran::class, 'histori_id');
    }
}

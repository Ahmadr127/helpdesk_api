<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyMap extends Model
{
    protected $table = 'legacy_map';

    protected $fillable = [
        'source_table',
        'source_id',
        'pengajuan_id',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_id');
    }
}

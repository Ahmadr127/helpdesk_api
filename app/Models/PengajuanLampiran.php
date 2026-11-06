<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanLampiran extends Model
{
    protected $table = 'pengajuan_lampiran';

    protected $fillable = [
        'pengajuan_id',
        'histori_id',
        'pesan_id',
        'kind',
        'path',
        'original_name',
        'mime',
        'size',
        'uploaded_by',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_id');
    }

    public function histori(): BelongsTo
    {
        return $this->belongsTo(PengajuanHistori::class, 'histori_id');
    }

    public function pesan(): BelongsTo
    {
        return $this->belongsTo(PengajuanPesan::class, 'pesan_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

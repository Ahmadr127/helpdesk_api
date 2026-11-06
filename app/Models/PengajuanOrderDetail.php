<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanOrderDetail extends Model
{
    protected $table = 'pengajuan_order_detail';

    protected $primaryKey = 'pengajuan_id';

    public $incrementing = false;

    protected $fillable = [
        'pengajuan_id',
        'unit_penerima',
        'kode_inventaris',
        'nama_barang',
        'unit_proses_id',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class, 'pengajuan_id');
    }

    public function unitProses(): BelongsTo
    {
        return $this->belongsTo(UnitProses::class, 'unit_proses_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSequence extends Model
{
    protected $table = 'document_sequences';

    protected $fillable = [
        'jenis_id',
        'period',
        'last_value',
    ];

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisPengajuan::class, 'jenis_id');
    }
}

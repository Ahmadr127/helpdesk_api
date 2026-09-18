<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    protected $fillable = ['name', 'code', 'status', 'location_id', 'building_id'];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Gedung milik departemen (single source of truth),
     * tidak lagi diturunkan dari lokasi.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }
}

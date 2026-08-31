<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitProses extends Model
{
    protected $fillable = ['name', 'code', 'status'];

    /**
     * Get the categories that belong to this unit proses
     */
    public function categories()
    {
        return $this->hasMany(Category::class, 'unit_proses_id');
    }
} 
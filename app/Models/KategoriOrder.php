<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriOrder extends Model
{
    use SoftDeletes;

    protected $table = 'kategori_order';

    protected $fillable = ['name', 'code', 'status'];
}

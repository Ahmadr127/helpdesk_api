<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriOrder extends Model
{
    use SoftDeletes;

    protected $table = 'kategori_order';

    protected $fillable = ['name', 'code', 'status'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kategori_order_user', 'kategori_order_id', 'user_id')->withTimestamps();
    }
}

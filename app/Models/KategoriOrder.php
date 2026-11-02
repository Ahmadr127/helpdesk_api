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

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'kategori_order_department', 'kategori_order_id', 'department_id')->withTimestamps();
    }

    /**
     * Kategori yang boleh dilihat/dipilih oleh user tertentu.
     * Kategori tanpa assignment = terlihat semua user (backward compat).
     */
    public function scopeForUser($query, ?User $user = null)
    {
        if (! $user) {
            return $query->where('status', 1);
        }

        return $query->where('status', 1)->where(function ($q) use ($user) {
            $q->where(function ($q2) {
                $q2->whereDoesntHave('users')->whereDoesntHave('departments');
            })
            ->orWhereHas('users', fn ($u) => $u->where('users.id', $user->id))
            ->orWhereHas('departments', function ($d) use ($user) {
                if (! empty($user->department_id)) {
                    $d->where('departments.id', $user->department_id);
                } elseif ($user->department) {
                    $d->where('code', $user->department)
                        ->orWhere('name', $user->department);
                }
            });
        });
    }
}

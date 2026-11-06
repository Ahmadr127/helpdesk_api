<?php

namespace App\Services\Pengajuan;

use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\PengajuanPenugasan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PengajuanScopeService
{
    public function categoryIdsFor(User $user): array
    {
        if ($user->hasPermission('pengajuan.view_all')) {
            return KategoriPengajuan::pluck('id')->all();
        }

        $role = Role::where('slug', $user->role)->first();

        return $role
            ? $role->kategoris()->pluck('kategori_pengajuan.id')->all()
            : [];
    }

    public function visibleTo(User $user): Builder
    {
        if ($user->hasPermission('pengajuan.view_all')) {
            return Pengajuan::query();
        }

        $allowedCategoryIds = $this->categoryIdsFor($user);

        return Pengajuan::query()->where(function (Builder $query) use ($user, $allowedCategoryIds): void {
            $query->where('pemohon_id', $user->id);

            if ($allowedCategoryIds !== []) {
                $query->orWhereIn('kategori_id', $allowedCategoryIds);
            }

            $query->orWhereHas('penugasan', function (Builder $assignment) use ($user): void {
                $assignment->where('user_id', $user->id)
                    ->whereNull('released_at');
            });
        });
    }

    public function canView(User $user, Pengajuan $pengajuan): bool
    {
        if ($user->hasPermission('pengajuan.view_all')) {
            return true;
        }

        if ((int) $pengajuan->pemohon_id === (int) $user->id) {
            return true;
        }

        if (in_array((int) $pengajuan->kategori_id, $this->categoryIdsFor($user), true)) {
            return true;
        }

        return PengajuanPenugasan::where('pengajuan_id', $pengajuan->id)
            ->where('user_id', $user->id)
            ->whereNull('released_at')
            ->exists();
    }

    public function canAct(User $user, Pengajuan $pengajuan): bool
    {
        if ($user->hasPermission('pengajuan.view_all')) {
            return true;
        }

        if (in_array((int) $pengajuan->kategori_id, $this->categoryIdsFor($user), true)) {
            return true;
        }

        return PengajuanPenugasan::where('pengajuan_id', $pengajuan->id)
            ->where('user_id', $user->id)
            ->whereNull('released_at')
            ->exists();
    }
}

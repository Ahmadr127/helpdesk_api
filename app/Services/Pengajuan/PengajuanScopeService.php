<?php

namespace App\Services\Pengajuan;

use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
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

        // Sumber tunggal: role_kategori. Role tanpa mapping = tanpa scope (fail-closed),
        // bukan "semua kategori sejenis". Ini yang membuat admin hanya melihat
        // pengajuan dari kategori yang benar-benar ada di datanya role.
        return $role
            ? $role->kategoris()->pluck('kategori_pengajuan.id')->map(fn ($id) => (int) $id)->all()
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

            // PJ aktif selalu boleh melihat pengajuan yang ditugaskan padanya,
            // walau kategori-nya di luar role_kategori (penugasan aktual > scope role).
            $query->orWhereHas('penugasan', function (Builder $penugasan) use ($user): void {
                $penugasan->where('user_id', $user->id)->whereNull('released_at');
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

        if ($this->isActivePic($user, $pengajuan)) {
            return true;
        }

        return in_array((int) $pengajuan->kategori_id, $this->categoryIdsFor($user), true);
    }

    public function canAct(User $user, Pengajuan $pengajuan): bool
    {
        if ($user->hasPermission('pengajuan.view_all')) {
            return true;
        }

        if ($this->isActivePic($user, $pengajuan)) {
            return true;
        }

        return in_array((int) $pengajuan->kategori_id, $this->categoryIdsFor($user), true);
    }

    /**
     * Apakah user adalah PJ aktif pada pengajuan ini (penugasan aktual).
     * Penugasan aktual memberi hak aksi pada satu pengajuan; role-kategori
     * memberi hak scope (docs 03-rbac-redesign:125).
     */
    public function isActivePic(User $user, Pengajuan $pengajuan): bool
    {
        if ($pengajuan->relationLoaded('penugasan')) {
            return $pengajuan->penugasan->contains(
                fn ($a) => (int) $a->user_id === (int) $user->id && $a->released_at === null
            );
        }

        return $pengajuan->penugasan()
            ->where('user_id', $user->id)
            ->whereNull('released_at')
            ->exists();
    }
}

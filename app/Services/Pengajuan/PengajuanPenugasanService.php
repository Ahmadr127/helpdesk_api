<?php

namespace App\Services\Pengajuan;

use App\Models\Pengajuan;
use App\Models\PengajuanPenugasan;
use App\Models\Role;
use App\Models\RoleKategori;
use App\Models\User;
use Illuminate\Support\Collection;

class PengajuanPenugasanService
{
    /**
     * User yang berhak menjadi PJ untuk kategori pengajuan ini,
     * yaitu user aktif yang rolenya terhubung ke kategori via role_kategori.
     */
    public function candidates(Pengajuan $pengajuan): Collection
    {
        $roleIds = RoleKategori::where('kategori_id', $pengajuan->kategori_id)
            ->pluck('role_id');

        $roleSlugs = Role::whereIn('id', $roleIds)->pluck('slug');

        return User::whereIn('role', $roleSlugs)
            ->where('status', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Apakah user merupakan kandidat PJ yang valid untuk pengajuan ini.
     */
    public function isCandidate(User $user, Pengajuan $pengajuan): bool
    {
        return $this->candidates($pengajuan)->contains('id', $user->id);
    }

    /**
     * Auto-assign semua kandidat PJ aktif yang belum ditugaskan.
     */
    public function autoAssign(Pengajuan $pengajuan, ?User $actor = null): void
    {
        foreach ($this->candidates($pengajuan) as $user) {
            $this->assignOne($pengajuan, $user, $actor);
        }
    }

    /**
     * Assign satu user sebagai PJ, dengan validasi role-kategori.
     */
    public function assignOne(Pengajuan $pengajuan, User $user, ?User $actor = null): PengajuanPenugasan
    {
        if (! $this->isCandidate($user, $pengajuan)) {
            throw new \InvalidArgumentException(
                "User {$user->name} bukan kandidat PJ untuk kategori pengajuan ini."
            );
        }

        $existing = $pengajuan->penugasan()
            ->where('user_id', $user->id)
            ->whereNull('released_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $role = Role::where('slug', $user->role)->first();

        return $pengajuan->penugasan()->create([
            'user_id' => $user->id,
            'role_id' => $role?->id,
            'assigned_by' => $actor?->id,
            'assigned_at' => now(),
        ]);
    }

    /**
     * Assign beberapa user sekaligus, hanya yang valid.
     */
    public function assign(Pengajuan $pengajuan, array $userIds, ?User $actor = null): void
    {
        $validIds = $this->candidates($pengajuan)->pluck('id')->map('intval')->all();

        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            if (! in_array($userId, $validIds, true)) {
                continue;
            }

            $user = User::find($userId);
            if ($user) {
                $this->assignOne($pengajuan, $user, $actor);
            }
        }
    }

    /**
     * Release (non-aktifkan) assignment user tertentu, atau semua jika userId null.
     */
    public function release(Pengajuan $pengajuan, ?int $userId = null): int
    {
        $query = $pengajuan->penugasan()->whereNull('released_at');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->update(['released_at' => now()]);
    }

    /**
     * Daftar PJ aktif untuk pengajuan.
     */
    public function active(Pengajuan $pengajuan): Collection
    {
        return $pengajuan->penugasan()
            ->whereNull('released_at')
            ->with('user')
            ->get();
    }

    /**
     * Apakah user adalah PJ aktif pada pengajuan.
     */
    public function isActive(User $user, Pengajuan $pengajuan): bool
    {
        return $pengajuan->penugasan()
            ->where('user_id', $user->id)
            ->whereNull('released_at')
            ->exists();
    }
}

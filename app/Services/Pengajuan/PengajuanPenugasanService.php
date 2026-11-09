<?php

namespace App\Services\Pengajuan;

use App\Models\Pengajuan;
use App\Models\PengajuanPenugasan;
use App\Models\Role;
use App\Models\RoleKategori;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

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

        // Sumber tunggal: role_kategori. Kategori tanpa mapping = tanpa kandidat PJ
        // (fail-closed), bukan "semua role pemegang dashboard jenis".
        if ($roleSlugs->isEmpty()) {
            return collect();
        }

        return User::whereIn('role', $roleSlugs)
            ->where('status', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Payload kandidat PJ untuk dropdown admin/Flutter (sumber: role_kategori).
     */
    public function candidatePayload(Pengajuan $pengajuan): array
    {
        return $this->candidates($pengajuan)->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'role_name' => $user->roleModel?->name,
            'department' => $user->department,
            'position' => $user->position,
        ])->values()->all();
    }

    /**
     * Payload PJ aktif (released_at IS NULL) untuk Flutter.
     */
    public function activePayload(Pengajuan $pengajuan): array
    {
        return $this->active($pengajuan)->map(fn (PengajuanPenugasan $a) => [
            'id' => $a->id,
            'user_id' => $a->user_id,
            'name' => $a->user?->name,
            'role_id' => $a->role_id,
            'role' => $a->role?->slug,
            'assigned_by' => $a->assigned_by,
            'assigned_at' => $a->assigned_at?->toIso8601String(),
        ])->values()->all();
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
     * Assign beberapa user sekaligus, hanya kandidat PJ valid dari role_kategori.
     * Jika kategori belum punya kandidat (role_kategori kosong), tidak ada yang di-assign.
     */
    public function assign(Pengajuan $pengajuan, array $userIds, ?User $actor = null): void
    {
        $validIds = $this->candidates($pengajuan)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();

        foreach ($userIds as $userId) {
            $userId = (int) $userId;
            if (! in_array($userId, $validIds, true)) {
                continue;
            }

            $user = User::find($userId);
            if ($user) {
                $existing = $pengajuan->penugasan()
                    ->where('user_id', $user->id)
                    ->whereNull('released_at')
                    ->first();
                if ($existing) {
                    continue;
                }
                $role = Role::where('slug', $user->role)->first();
                $pengajuan->penugasan()->create([
                    'user_id' => $user->id,
                    'role_id' => $role?->id,
                    'assigned_by' => $actor?->id,
                    'assigned_at' => now(),
                ]);
            }
        }
    }

    /**
     * Sinkronkan PJ aktif agar persis sesuai $userIds (hanya kandidat valid dari role_kategori).
     * Jika kategori belum punya kandidat, daftar valid kosong (semua PJ aktif akan dilepas
     * bila $userIds diisi dengan non-kandidat; kirim $userIds=[] untuk melepas semua).
     * Mengembalikan daftar user yang baru ditugaskan (untuk notifikasi).
     */
    public function sync(Pengajuan $pengajuan, array $userIds, ?User $actor = null): array
    {
        $validIds = $this->candidates($pengajuan)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $desired = collect($userIds)->filter()->map(fn ($v) => (int) $v)->unique()->values()
            ->filter(fn ($id) => in_array($id, $validIds, true))->values();

        $now = now();
        $active = $pengajuan->penugasan()->whereNull('released_at')->get();
        foreach ($active as $a) {
            if (! $desired->contains((int) $a->user_id)) {
                $a->update(['released_at' => $now]);
            }
        }

        $newlyAssigned = [];
        foreach ($desired as $uid) {
            $exists = $pengajuan->penugasan()
                ->where('user_id', $uid)->whereNull('released_at')->exists();
            if (! $exists) {
                $user = User::find($uid);
                $role = $user ? Role::where('slug', $user->role)->first() : null;
                $pengajuan->penugasan()->create([
                    'pengajuan_id' => $pengajuan->id,
                    'user_id' => $uid,
                    'role_id' => $role?->id,
                    'assigned_by' => $actor?->id,
                    'assigned_at' => $now,
                ]);
                if ($user) {
                    $newlyAssigned[] = $user;
                }
            }
        }
        return $newlyAssigned;
    }

    /**
     * Sinkronkan PJ aktif dengan validasi ketat: user di luar kandidat
     * role_kategori ditolak (422), bukan dibuang diam-diam.
     * Mengembalikan daftar user yang baru ditugaskan (untuk notifikasi).
     */
    public function syncOrFail(Pengajuan $pengajuan, array $userIds, ?User $actor = null): array
    {
        $validIds = $this->candidates($pengajuan)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $requested = collect($userIds)
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        $invalid = $requested->reject(fn ($id) => in_array($id, $validIds, true))->values();

        if ($invalid->isNotEmpty()) {
            $names = User::whereIn('id', $invalid->all())->pluck('name', 'id');

            throw ValidationException::withMessages([
                'user_ids' => 'User berikut bukan kandidat PJ untuk kategori pengajuan ini: '
                    .$invalid->map(fn ($id) => $names[$id] ?? "#{$id}")->implode(', '),
            ]);
        }

        return $this->sync($pengajuan, $requested->all(), $actor);
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
            ->with(['user', 'role'])
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

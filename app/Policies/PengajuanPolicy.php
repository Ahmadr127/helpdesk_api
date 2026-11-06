<?php

namespace App\Policies;

use App\Models\Pengajuan;
use App\Models\User;
use App\Services\Pengajuan\PengajuanScopeService;

class PengajuanPolicy
{
    public function __construct(protected PengajuanScopeService $scope) {}

    public function view(User $user, Pengajuan $pengajuan): bool
    {
        return $this->scope->canView($user, $pengajuan);
    }

    public function update(User $user, Pengajuan $pengajuan): bool
    {
        return (int) $pengajuan->pemohon_id === (int) $user->id && $pengajuan->status === 'open';
    }

    public function delete(User $user, Pengajuan $pengajuan): bool
    {
        return (int) $pengajuan->pemohon_id === (int) $user->id && $pengajuan->status === 'open';
    }

    public function act(User $user, Pengajuan $pengajuan): bool
    {
        return $this->scope->canAct($user, $pengajuan);
    }
}
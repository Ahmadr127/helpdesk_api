<?php

namespace App\Observers;

use App\Models\Pengajuan;
use App\Services\AuditService;

class PengajuanAuditObserver
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function created(Pengajuan $pengajuan): void
    {
        $this->auditService->log('created', $pengajuan, [], $pengajuan->toArray());
    }

    public function updated(Pengajuan $pengajuan): void
    {
        $this->auditService->log('updated', $pengajuan, $pengajuan->getOriginal(), $pengajuan->getChanges());
    }

    public function deleted(Pengajuan $pengajuan): void
    {
        $this->auditService->log('deleted', $pengajuan, $pengajuan->toArray(), []);
    }
}
<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer audit log generik — otomatis capture created/updated/deleted
 * untuk semua model yang terdaftar di config/audit.php.
 *
 * Menambah model baru cukup menambah entri di config/audit.php
 * (satu lokasi) — tanpa membuat observer baru per model.
 */
class AuditObserver
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function created(Model $auditable): void
    {
        $this->auditService->log('created', $auditable, [], $auditable->getAttributes());
    }

    public function updated(Model $auditable): void
    {
        $this->auditService->log('updated', $auditable, $auditable->getOriginal(), $auditable->getChanges());
    }

    public function deleted(Model $auditable): void
    {
        $this->auditService->log('deleted', $auditable, $auditable->getAttributes(), []);
    }
}

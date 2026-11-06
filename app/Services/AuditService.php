<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function log(string $event, ?Model $auditable = null, array $oldValues = [], array $newValues = [], ?Request $request = null): void
    {
        $request = $request ?: request();
        $user = Auth::user();

        AuditLog::create([
            'user_id' => $user?->id,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->getKey(),
            'event' => $event,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'url' => $request?->fullUrl(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public function logPivot(string $event, array $oldValues = [], array $newValues = [], ?Request $request = null): void
    {
        $this->log($event, null, $oldValues, $newValues, $request);
    }
}
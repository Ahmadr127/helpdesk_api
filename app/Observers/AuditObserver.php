<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditObserver
{
    protected static array $oldValues = [];

    public function created(Model $model): void
    {
        $this->log('created', $model, [], $model->getAttributes());
    }

    public function updating(Model $model): void
    {
        self::$oldValues[spl_object_id($model)] = $model->getOriginal();
    }

    public function updated(Model $model): void
    {
        $old = self::$oldValues[spl_object_id($model)] ?? $model->getOriginal();
        unset(self::$oldValues[spl_object_id($model)]);

        $new = $model->getChanges();

        if (empty($new)) {
            return;
        }

        $oldFiltered = array_intersect_key($old, $new);
        $this->log('updated', $model, $oldFiltered, $new);
    }

    public function deleted(Model $model): void
    {
        $this->log('deleted', $model, $model->getOriginal(), []);
    }

    public function restored(Model $model): void
    {
        $this->log('restored', $model, [], $model->getAttributes());
    }

    protected function log(string $event, Model $model, array $oldValues, array $newValues): void
    {
        // Hindari audit pada AuditLog sendiri (rekursif)
        if ($model instanceof AuditLog) {
            return;
        }

        // Hilangkan field sensitif / timestamps yang tidak perlu
        $hidden = ['password', 'remember_token', 'updated_at'];
        foreach ($hidden as $key) {
            unset($oldValues[$key], $newValues[$key]);
        }

        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'event' => $event,
                'old_values' => empty($oldValues) ? null : $oldValues,
                'new_values' => empty($newValues) ? null : $newValues,
                'url' => Request::fullUrl(),
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Audit log gagal: '.$e->getMessage());
        }
    }
}

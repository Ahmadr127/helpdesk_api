<?php

namespace App\Console\Commands\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait MapsLegacyPengajuan
{
    protected function mapPrioritas(?string $p): string
    {
        $v = strtolower(trim((string) $p));

        return match (true) {
            in_array($v, ['high', 'tinggi', 'urgent', 'tinggi/urgent'], true) => 'high',
            in_array($v, ['low', 'rendah'], true) => 'low',
            default => 'medium',
        };
    }

    protected function mapStatus(?string $status): string
    {
        return match (strtolower(trim((string) $status))) {
            'tutup', 'ditutup', 'closed', 'pending' => 'ditutup',
            'confirmed', 'completed', 'done' => 'confirmed',
            'rejected' => 'rejected',
            'in_progress', 'process' => 'in_progress',
            default => 'open',
        };
    }

    protected function parseDate($value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        // Data legacy double-encoded: di DB tersimpan sebagai "\"[{...}]\"" (JSON string berisi JSON array)
        $v = trim($value);
        for ($i = 0; $i < 3; $i++) {
            $d = json_decode($v, true);
            if (is_array($d)) {
                if (count($d) === 1 && isset($d[0]) && is_string($d[0]) && str_starts_with(trim((string) $d[0]), '[')) {
                    $v = trim((string) $d[0]);
                    continue;
                }
                return $d;
            }
            if (is_string($d) && str_starts_with(trim($d), '[')) {
                $v = trim($d);
                continue;
            }
            return [];
        }
        return [];
    }

    protected function firstUserId(): ?int
    {
        return DB::table('users')->orderBy('id')->value('id');
    }

    protected function historiExists(int $pengajuanId, string $action, ?string $notes, $createdAt): bool
    {
        $notes = $notes !== null && trim($notes) === '' ? null : $notes;
        $q = DB::table('pengajuan_histori')
            ->where('pengajuan_id', $pengajuanId)
            ->where('action', $action);

        if ($notes !== null) {
            $q->where('notes', $notes);
        } else {
            $q->where(function ($qq) {
                $qq->whereNull('notes')->orWhere('notes', '');
            });
        }

        if ($createdAt !== null) {
            $q->where('created_at', $createdAt);
        }

        return $q->exists();
    }

    protected function pesanExists(int $pengajuanId, ?int $senderId, string $body, $createdAt): bool
    {
        $q = DB::table('pengajuan_pesan')
            ->where('pengajuan_id', $pengajuanId)
            ->where('body', $body);

        if ($senderId !== null) {
            $q->where('sender_id', $senderId);
        } else {
            $q->whereNull('sender_id');
        }

        if ($createdAt !== null) {
            $q->where('created_at', $createdAt);
        }

        return $q->exists();
    }
}

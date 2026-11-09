<?php

namespace App\Console\Commands\Concerns;

use App\Models\Pengajuan;
use App\Models\PengajuanPesan;

trait MigratesTicketMessages
{
    protected function migrateTicketMessages(Pengajuan $pengajuan, object $ticket, int $pemohonId, bool $dry = false): array
    {
        $stats = ['pesan' => 0, 'histori' => 0, 'lampiran' => 0];

        foreach ($this->decodeJson($ticket->admin_responses ?? null) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $body = $item['notes'] ?? $item['message'] ?? null;
            if (! is_string($body) || trim($body) === '') {
                continue;
            }

            $timestamp = $this->parseDate($item['timestamp'] ?? null) ?? $pengajuan->created_at;
            $adminId = isset($item['admin_id']) ? (int) $item['admin_id'] : $pengajuan->created_by;
            $status = isset($item['status']) ? $this->mapStatus((string) $item['status']) : $pengajuan->status;

            if ($dry) {
                $stats['pesan']++;
                $stats['histori']++;
                if (! empty($item['photo'])) {
                    $stats['lampiran']++;
                }
                continue;
            }

            if ($this->pesanExists($pengajuan->id, $adminId, $body, $timestamp)) {
                continue;
            }

            $pesan = $pengajuan->pesans()->create([
                'sender_id' => $adminId,
                'sender_type' => 'admin',
                'body' => $body,
            ]);
            $pesan->created_at = $timestamp;
            $pesan->updated_at = $timestamp;
            $pesan->saveQuietly();
            $stats['pesan']++;

            if (! $this->historiExists($pengajuan->id, 'admin_response', $body, $timestamp)) {
                $this->createHistori($pengajuan, $status, 'admin_response', $body, $adminId, 'user', $timestamp);
                $stats['histori']++;
            }

            if (! empty($item['photo'])) {
                $before = (int) \Illuminate\Support\Facades\DB::table('pengajuan_lampiran')->where('pengajuan_id', $pengajuan->id)->where('path', (string) $item['photo'])->count();
                $this->createLampiran($pengajuan, (string) $item['photo'], 'admin_response', $adminId, $timestamp, $pesan->id);
                $after = (int) \Illuminate\Support\Facades\DB::table('pengajuan_lampiran')->where('pengajuan_id', $pengajuan->id)->where('path', (string) $item['photo'])->count();
                if ($after > $before) {
                    $stats['lampiran']++;
                }
            }
        }

        foreach ($this->decodeJson($ticket->user_replies ?? null) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $body = $item['message'] ?? $item['notes'] ?? null;
            if (! is_string($body) || trim($body) === '') {
                continue;
            }

            $timestamp = $this->parseDate($item['timestamp'] ?? null) ?? $pengajuan->created_at;
            $type = (string) ($item['type'] ?? '');
            $kind = $type === 'reject' ? 'user_rejection' : 'user_response';

            if ($type === 'confirm') {
                if ($dry) {
                    $stats['histori']++;
                    if (! empty($item['photo'])) {
                        $stats['lampiran']++;
                    }
                    continue;
                }

                if (! $this->historiExists($pengajuan->id, 'confirmed', $body, $timestamp)) {
                    $this->createHistori($pengajuan, 'confirmed', 'confirmed', $body, $pemohonId, 'user', $timestamp);
                    $stats['histori']++;
                }

                if (! empty($item['photo'])) {
                    $before = (int) \Illuminate\Support\Facades\DB::table('pengajuan_lampiran')->where('pengajuan_id', $pengajuan->id)->where('path', (string) $item['photo'])->count();
                    $this->createLampiran($pengajuan, (string) $item['photo'], 'user_response', $pemohonId, $timestamp);
                    $after = (int) \Illuminate\Support\Facades\DB::table('pengajuan_lampiran')->where('pengajuan_id', $pengajuan->id)->where('path', (string) $item['photo'])->count();
                    if ($after > $before) {
                        $stats['lampiran']++;
                    }
                }

                continue;
            }

            if ($dry) {
                $stats['pesan']++;
                $stats['histori']++;
                if (! empty($item['photo'])) {
                    $stats['lampiran']++;
                }
                continue;
            }

            if ($this->pesanExists($pengajuan->id, $pemohonId, $body, $timestamp)) {
                continue;
            }

            $pesan = $pengajuan->pesans()->create([
                'sender_id' => $pemohonId,
                'sender_type' => 'user',
                'body' => $body,
            ]);
            $pesan->created_at = $timestamp;
            $pesan->updated_at = $timestamp;
            $pesan->saveQuietly();
            $stats['pesan']++;

            if (! $this->historiExists($pengajuan->id, 'user_reply', $body, $timestamp)) {
                $this->createHistori($pengajuan, $pengajuan->status, 'user_reply', $body, $pemohonId, 'user', $timestamp);
                $stats['histori']++;
            }

            if (! empty($item['photo'])) {
                $before = (int) \Illuminate\Support\Facades\DB::table('pengajuan_lampiran')->where('pengajuan_id', $pengajuan->id)->where('path', (string) $item['photo'])->count();
                $this->createLampiran($pengajuan, (string) $item['photo'], $kind, $pemohonId, $timestamp, $pesan->id);
                $after = (int) \Illuminate\Support\Facades\DB::table('pengajuan_lampiran')->where('pengajuan_id', $pengajuan->id)->where('path', (string) $item['photo'])->count();
                if ($after > $before) {
                    $stats['lampiran']++;
                }
            }
        }

        return $stats;
    }
}

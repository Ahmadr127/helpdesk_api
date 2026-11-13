<?php

namespace App\Support;

use App\Models\Pengajuan;

/**
 * Pembangun data percakapan (bubble chat) untuk semua halaman detail
 * pengajuan — tiket maupun order, admin maupun user.
 *
 * Satu sumber kebenaran untuk urutan bubble, penggabungan pesan +
 * konfirmasi pemilik, serta lampiran yang menempel pada pesan, supaya
 * tampilan tidak lagi dikopi-tempel per halaman.
 */
class Conversation
{
    /**
     * Data bubble percakapan, urut kronologis (lama di atas), siap
     * dirender lewat components.conversation-history / chat-bubble.
     *
     * @return array<int, array{type: string, notes: string|null, message: string|null,
     *                          admin_name: string|null, photo: string|null,
     *                          timestamp: string|null, action: string|null}>
     */
    /** Timeline histori urut waktu — dipakai untuk menentukan status pada saat tertentu. */
    private static function timelineAt(Pengajuan $pengajuan, ?string $timestamp): ?string
    {
        $ts = $timestamp ? strtotime($timestamp) : false;
        if ($ts === false) {
            return null;
        }

        $status = null;
        foreach ($pengajuan->historis->sortBy(fn ($h) => $h->created_at?->getTimestamp() ?? 0) as $h) {
            if (! $h->created_at || $h->created_at->getTimestamp() > $ts) {
                break;
            }
            $status = $h->status ?: $status;
        }

        return $status ?? 'open';
    }

    /** Tambahkan status saat itu (point-in-time) ke setiap bubble. */
    private static function annotateStatus(array $responses, Pengajuan $pengajuan): array
    {
        return array_map(function ($item) use ($pengajuan) {
            $status = self::timelineAt($pengajuan, $item['timestamp'] ?? null);
            $item['status'] = $status;
            $item['status_label'] = match ($status) {
                'open' => 'Terbuka',
                'in_progress' => 'Dalam Proses',
                'ditutup' => 'Ditutup',
                'confirmed' => 'Dikonfirmasi',
                'rejected' => 'Ditolak',
                default => ucfirst(str_replace('_', ' ', (string) $status)),
            };

            return $item;
        }, $responses);
    }

    public static function responses(Pengajuan $pengajuan): array
    {
        $lampiranByPesan = self::lampiranByPesan($pengajuan);

        $responses = $pengajuan->pesans
            ->map(function ($pesan) use ($lampiranByPesan) {
                $isAdmin = ($pesan->sender_type ?? '') === 'admin';

                return [
                    'type' => $isAdmin ? 'admin' : 'user',
                    'notes' => $pesan->body,
                    'message' => $pesan->body,
                    'admin_name' => $pesan->sender?->name ?? ($isAdmin ? 'Admin' : null),
                    'photo' => $lampiranByPesan->get($pesan->id)?->first()?->path,
                    'timestamp' => $pesan->created_at?->toDateTimeString() ?? $pesan->created_at,
                    'action' => null,
                ];
            })
            ->sortBy('timestamp')
            ->values()
            ->all();

        if (count($responses) > 0) {
            // Balasan konfirmasi hanya jadi histori action=confirmed — gabungkan
            // sebagai bubble pengaju agar percakapan tampil utuh.
            $confirmBubbles = $pengajuan->historis
                ->where('action', 'confirmed')
                ->filter(fn ($h) => ($h->actor_type ?? '') === 'user'
                    && trim((string) ($h->notes ?? '')) !== ''
                    && $h->notes !== 'Pengajuan dikonfirmasi.')
                ->map(fn ($h) => [
                    'type' => 'user',
                    'notes' => $h->notes,
                    'message' => $h->notes,
                    'admin_name' => null,
                    'photo' => null,
                    'timestamp' => $h->created_at?->toDateTimeString() ?? $h->created_at,
                    'action' => 'confirm',
                ])
                ->values()
                ->all();

            $final = collect($responses)
                ->concat($confirmBubbles)
                ->sortBy('timestamp')
                ->values()
                ->all();

            return self::annotateStatus($final, $pengajuan);
        }

        // Fallback: histori lama (pra-unifikasi) yang belum jadi pesan.
        $fallback = $pengajuan->historis
            ->whereIn('action', ['admin_response', 'user_reply', 'confirmed'])
            ->map(function ($h) {
                $isAdmin = ($h->action ?? '') === 'admin_response';

                return [
                    'type' => $isAdmin ? 'admin' : 'user',
                    'notes' => $h->notes,
                    'message' => $h->notes,
                    'admin_name' => $h->actor?->name ?? ($isAdmin ? 'Admin' : null),
                    'photo' => null,
                    'timestamp' => $h->created_at?->toDateTimeString() ?? $h->created_at,
                    'action' => ($h->action ?? '') === 'confirmed' ? 'confirm' : null,
                ];
            })
            ->sortBy('timestamp')
            ->values()
            ->all();

        return self::annotateStatus($fallback, $pengajuan);
    }

    /** Lampiran yang menempel pada pesan (pesan_id), dikelompokkan per pesan. */
    public static function lampiranByPesan(Pengajuan $pengajuan)
    {
        return $pengajuan->lampirans->whereNotNull('pesan_id')->groupBy('pesan_id');
    }

    /** Lampiran awal pengajuan (foto yang diunggah saat membuat pengajuan). */
    public static function initialLampirans(Pengajuan $pengajuan)
    {
        return $pengajuan->lampirans->where('kind', 'initial')->values();
    }
}

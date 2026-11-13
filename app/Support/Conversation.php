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

            return collect($responses)
                ->concat($confirmBubbles)
                ->sortBy('timestamp')
                ->values()
                ->all();
        }

        // Fallback: histori lama (pra-unifikasi) yang belum jadi pesan.
        return $pengajuan->historis
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

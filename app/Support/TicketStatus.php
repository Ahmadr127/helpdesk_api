<?php

namespace App\Support;

/**
 * Sumber tunggal daftar status pengajuan untuk filter & label di web.
 *
 * Status kanonik yang benar-benar tersimpan di kolom `pengajuan.status`:
 * open, in_progress, ditutup, confirmed, rejected.
 *
 * `closed`, `tutup`, `pending`, dan `pending_confirmation` hanya alias lama
 * (bookmark/URL/legacy view). Sebelumnya filter mengirim `status=closed`
 * langsung ke query sehingga selalu 0 baris, karena DB menyimpan `ditutup`.
 * Semua alias sekarang dinormalisasi lewat [normalize].
 */
class TicketStatus
{
    public const OPEN = 'open';

    public const IN_PROGRESS = 'in_progress';

    public const DITUTUP = 'ditutup';

    public const CONFIRMED = 'confirmed';

    public const REJECTED = 'rejected';

    /**
     * Semua status kanonik (urutan mengikuti alur tiket).
     *
     * @return list<string>
     */
    public static function canonical(): array
    {
        return [self::OPEN, self::IN_PROGRESS, self::DITUTUP, self::CONFIRMED, self::REJECTED];
    }

    /**
     * Opsi untuk <select>: nilai kanonik => label Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::OPEN => 'Dibuka',
            self::IN_PROGRESS => 'Dalam Proses',
            self::DITUTUP => 'Menunggu Konfirmasi',
            self::CONFIRMED => 'Dikonfirmasi',
            self::REJECTED => 'Ditolak',
        ];
    }

    /**
     * Normalisasi input filter ke status kanonik.
     * Mengembalikan null untuk "semua status" / nilai kosong.
     */
    public static function normalize(?string $value): ?string
    {
        $v = strtolower(trim((string) $value));

        if ($v === '' || $v === 'all') {
            return null;
        }

        return match ($v) {
            'closed', 'tutup', 'pending', 'pending_confirmation' => self::DITUTUP,
            'in-progress' => self::IN_PROGRESS,
            default => $v,
        };
    }

    /** Label siap tampil untuk sebuah status. */
    public static function label(?string $status): string
    {
        $canonical = self::normalize($status);

        if ($canonical === null) {
            return '-';
        }

        return self::options()[$canonical] ?? ucfirst(str_replace('_', ' ', $canonical));
    }

    /** Kelas Tailwind badge untuk sebuah status. */
    public static function badgeClass(?string $status): string
    {
        return match (self::normalize($status)) {
            self::OPEN => 'bg-blue-100 text-blue-800',
            self::IN_PROGRESS => 'bg-yellow-100 text-yellow-800',
            self::DITUTUP => 'bg-violet-100 text-violet-800',
            self::CONFIRMED => 'bg-green-100 text-green-800',
            self::REJECTED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}

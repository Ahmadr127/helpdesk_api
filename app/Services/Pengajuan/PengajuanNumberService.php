<?php

namespace App\Services\Pengajuan;

use App\Models\DocumentSequence;
use App\Models\JenisPengajuan;
use App\Models\Pengajuan;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PengajuanNumberService
{
    public function next(string $jenisCode, ?\Carbon\Carbon $date = null): string
    {
        $date ??= now();
        $jenis = JenisPengajuan::where('code', $jenisCode)->firstOrFail();

        // Period length follows current data layout: short prefix (P, UMUM) == 'dm', long prefix (e.g. OP/RTG/MTC) == 'Ymd'.
        // A new jenis can override by renaming itself accordingly; for most prefixes 'dm' is fine too.
        $period = $jenisCode === 'IT'
            ? $date->format('dm')
            : $date->format('Ymd');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($jenis, $jenisCode, $period): string {
                    $sequence = DocumentSequence::where('jenis_id', $jenis->id)
                        ->where('period', $period)
                        ->lockForUpdate()
                        ->first();

                    if (! $sequence) {
                        $sequence = DocumentSequence::create([
                            'jenis_id' => $jenis->id,
                            'period' => $period,
                            'last_value' => $this->legacyMaxSequence($jenis, $period),
                        ]);
                    }

                    // Self-heal: data legacy / out-of-sync dapat menyebabkan
                    // sequence tertinggal dari max nomor existing. Naikkan
                    // jika perlu sebelum increment, agar nomor baru tidak
                    // bertabrakan.
                    $existingMax = $this->legacyMaxSequence($jenis, $period);
                    if ((int) $sequence->last_value < $existingMax) {
                        $sequence->last_value = $existingMax;
                        $sequence->save();
                    }

                    $sequence->increment('last_value');
                    $sequence->refresh();

                    return $this->format($jenis, $period, (int) $sequence->last_value);
                });
            } catch (QueryException $e) {
                if ($attempt === 2 || ! $this->isUniqueViolation($e)) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Gagal membuat nomor pengajuan.');
    }

    /**
     * Cari nomor terakhir dari data legacy agar sequence tidak tabrakan.
     * Pola dicocokkan terhadap prefix jenis dari DB.
     *
     * @return int
     */
    protected function legacyMaxSequence(JenisPengajuan $jenis, string $period): int
    {
        $max = 0;
        $prefix = $jenis->prefix ?? 'T';

        if ($jenis->code === 'IPSRS') {
            $pattern = '/^' . preg_quote($prefix, '/') . '-(\d{8})(\d{3})$/';
        } else {
            $pattern = '/^' . preg_quote($prefix, '/') . '-(\d{4})-(\d{3})$/';
        }

        Pengajuan::where('nomor', 'like', $prefix . '%')
            ->pluck('nomor')
            ->each(function (string $nomor) use (&$max, $pattern, $period): void {
                if (! preg_match($pattern, $nomor, $matches)) {
                    return;
                }

                if ($matches[1] !== $period) {
                    return;
                }

                $max = max($max, (int) $matches[2]);
            });

        return $max;
    }

    /** Format nomor pengajuan dari prefix jenis. */
    protected function format(JenisPengajuan $jenis, string $period, int $sequence): string
    {
        $prefix = $jenis->prefix ?? 'T';

        if ($jenis->code === 'IPSRS') {
            // Pola legacy: OP/RTG/MTC-YYYYMMDD003
            return sprintf('%s-%s%03d', $prefix, $period, $sequence);
        }

        // Pola umum: PREFIX-DDMM-001
        return sprintf('%s-%s-%03d', $prefix, $period, $sequence);
    }

    protected function isUniqueViolation(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'unique')
            || str_contains($e->getMessage(), 'duplicate key');
    }
}

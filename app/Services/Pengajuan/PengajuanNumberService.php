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
                            'last_value' => $this->legacyMaxSequence($jenisCode, $period),
                        ]);
                    }

                    $sequence->increment('last_value');
                    $sequence->refresh();

                    return $this->format($jenisCode, $jenis->prefix, $period, (int) $sequence->last_value);
                });
            } catch (QueryException $e) {
                if ($attempt === 2 || ! $this->isUniqueViolation($e)) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Gagal membuat nomor pengajuan.');
    }

    protected function legacyMaxSequence(string $jenisCode, string $period): int
    {
        $max = 0;

        if ($jenisCode === 'IT') {
            $pattern = '/^T-(\d{4})-(\d{3})$/';
        } else {
            $pattern = '/^OP\/RTG\/MTC-(\d{8})(\d{3})$/';
        }

        Pengajuan::where('nomor', 'like', $this->prefixFor($jenisCode) . '%')
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

    protected function prefixFor(string $jenisCode): string
    {
        return $jenisCode === 'IT' ? 'T' : 'OP/RTG/MTC';
    }

    protected function format(string $jenisCode, string $prefix, string $period, int $sequence): string
    {
        if ($jenisCode === 'IT') {
            return sprintf('T-%s-%03d', $period, $sequence);
        }

        return sprintf('OP/RTG/MTC-%s%03d', $period, $sequence);
    }

    protected function isUniqueViolation(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'unique')
            || str_contains($e->getMessage(), 'duplicate key');
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Pengajuan;
use App\Models\PengajuanHistori;
use App\Models\User;
use App\Support\TicketStatus;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Ubah status pengajuan (tiket IT / order IPSRS) dengan tanggal yang ditentukan.
 *
 * Dipakai untuk koreksi data: memperbaiki status yang salah, atau memundurkan
 * tanggal perubahan (mis. input tertinggal beberapa hari). Semua timestamp
 * terkait (started_at/closed_at/confirmed_at/rejected_at), `updated_at`, dan
 * baris histori ditulis memakai tanggal yang sama supaya laporan konsisten.
 *
 * Contoh:
 *   php artisan pengajuan:status "OP/RTG/MTC-20261110017" in_progress \
 *       --at="2026-11-10 23:50:18" --note="Mulai pengerjaan (koreksi data)" --actor=1
 *   php artisan pengajuan:status 52 ditutup --at="2026-11-12 09:00" --dry-run
 */
class ChangePengajuanStatus extends Command
{
    protected $signature = 'pengajuan:status
        {pengajuan : ID numerik atau nomor pengajuan (contoh: 52 / OP/RTG/MTC-20261110017)}
        {status : open|in_progress|ditutup|confirmed|rejected (alias: tutup/closed/pending_confirmation)}
        {--at= : Tanggal & waktu perubahan, mis. "2026-11-15 21:48:00". Default: sekarang}
        {--note= : Catatan untuk baris histori}
        {--actor= : ID atau email user pelaku (default: sistem)}
        {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Ubah status pengajuan beserta tanggalnya (timestamp + histori)';

    public function handle(): int
    {
        $pengajuan = $this->resolvePengajuan((string) $this->argument('pengajuan'));

        if ($pengajuan === null) {
            $this->error('Pengajuan tidak ditemukan: '.$this->argument('pengajuan'));

            return self::FAILURE;
        }

        $target = TicketStatus::normalize((string) $this->argument('status'));

        if ($target === null || ! in_array($target, TicketStatus::canonical(), true)) {
            $this->error('Status tidak dikenal: '.$this->argument('status'));
            $this->line('Pilihan: '.implode(', ', TicketStatus::canonical()));

            return self::FAILURE;
        }

        try {
            $at = $this->option('at')
                ? Carbon::parse((string) $this->option('at'))
                : now();
        } catch (\Throwable $e) {
            $this->error('Format --at tidak valid: '.$this->option('at'));

            return self::FAILURE;
        }

        $actor = null;
        if ($this->option('actor') !== null) {
            $actor = $this->resolveActor((string) $this->option('actor'));

            if ($actor === null) {
                $this->error('User (--actor) tidak ditemukan: '.$this->option('actor'));

                return self::FAILURE;
            }
        }

        $before = $this->snapshot($pengajuan);

        $attributes = $this->timestampAttributes($pengajuan, $target, $at);
        $attributes['status'] = $target;

        if ($actor !== null) {
            $attributes['updated_by'] = $actor->id;
        }

        if ($this->option('dry-run')) {
            $this->warn('[dry-run] Tidak ada perubahan yang disimpan.');
            $this->renderTable($before, $attributes + ['updated_at' => $at] + $before, $pengajuan, $target, $at);

            return self::SUCCESS;
        }

        // Timestamps dimatikan agar Eloquent tidak menimpa updated_at dengan now().
        // `updated_at` di-set langsung karena tidak termasuk $fillable.
        $pengajuan->timestamps = false;
        $pengajuan->fill($attributes);
        $pengajuan->updated_at = $at;
        $pengajuan->save();

        $histori = $pengajuan->historis()->make([
            'status' => $target,
            'action' => 'status_updated',
            'notes' => $this->option('note')
                ?: 'Status diubah menjadi '.TicketStatus::label($target).' (via artisan).',
            'actor_id' => $actor?->id,
            'actor_type' => PengajuanHistori::actorTypeFor($actor),
        ]);
        // Nilai created_at/updated_at di-set langsung (di luar $fillable) dan
        // sudah "dirty", sehingga Eloquent tidak menimpanya dengan now().
        $histori->created_at = $at;
        $histori->updated_at = $at;
        $histori->save();

        $pengajuan->timestamps = true;
        $pengajuan->refresh();

        $this->renderTable($before, $this->snapshot($pengajuan), $pengajuan, $target, $at);
        $this->info('Status tersimpan. Histori #'.$histori->id.' dibuat pada '.$at->format('Y-m-d H:i:s').'.');

        if ($before['status'] === $target) {
            $this->warn('Status tidak berubah (sudah '.TicketStatus::label($target).'); hanya timestamp + histori yang diperbarui.');
        }

        return self::SUCCESS;
    }

    protected function resolvePengajuan(string $input): ?Pengajuan
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        if (ctype_digit($input)) {
            return Pengajuan::find((int) $input);
        }

        return Pengajuan::whereRaw('UPPER(nomor) = ?', [strtoupper($input)])->first();
    }

    protected function resolveActor(string $input): ?User
    {
        $input = trim($input);

        if (ctype_digit($input)) {
            return User::find((int) $input);
        }

        return User::where('email', $input)->first();
    }

    /**
     * Timestamp yang harus ikut diisi/dibersihkan, disamakan dengan
     * PengajuanStatusService agar hasil command identik dengan alur web/API.
     *
     * @return array<string, mixed>
     */
    protected function timestampAttributes(Pengajuan $pengajuan, string $status, Carbon $at): array
    {
        return match ($status) {
            TicketStatus::IN_PROGRESS => [
                'started_at' => $pengajuan->started_at ?: $at,
                ...$this->reopenCleanup($pengajuan),
            ],
            TicketStatus::DITUTUP => [
                'closed_at' => $pengajuan->closed_at ?: $at,
                'started_at' => $pengajuan->started_at ?: $at,
                // Menutup (lagi) = menunggu konfirmasi user lagi.
                'confirmed_at' => null,
                'rejected_at' => null,
                'user_confirmation' => false,
                'user_confirmed_at' => null,
            ],
            TicketStatus::CONFIRMED => [
                'confirmed_at' => $pengajuan->confirmed_at ?: $at,
            ],
            TicketStatus::REJECTED => [
                'rejected_at' => $pengajuan->rejected_at ?: $at,
                'confirmed_at' => null,
                'user_confirmation' => false,
                'user_confirmed_at' => null,
            ],
            // Kembali ke open = buka ulang dari awal.
            TicketStatus::OPEN => [
                'started_at' => null,
                'closed_at' => null,
                'confirmed_at' => null,
                'rejected_at' => null,
                'user_confirmation' => false,
                'user_confirmed_at' => null,
            ],
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function reopenCleanup(Pengajuan $pengajuan): array
    {
        if (! in_array($pengajuan->status, [TicketStatus::DITUTUP, TicketStatus::CONFIRMED, TicketStatus::REJECTED], true)) {
            return [];
        }

        return [
            'closed_at' => null,
            'confirmed_at' => null,
            'rejected_at' => null,
            'user_confirmation' => false,
            'user_confirmed_at' => null,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function snapshot(Pengajuan $pengajuan): array
    {
        $format = fn ($value) => $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : '-';

        return [
            'nomor' => (string) $pengajuan->nomor,
            'status' => (string) $pengajuan->status,
            'started_at' => $format($pengajuan->started_at),
            'closed_at' => $format($pengajuan->closed_at),
            'confirmed_at' => $format($pengajuan->confirmed_at),
            'rejected_at' => $format($pengajuan->rejected_at),
            'updated_at' => $format($pengajuan->updated_at),
        ];
    }

    /**
     * Tampilkan perbandingan sebelum/sesudah dalam satu tabel.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    protected function renderTable(array $before, array $after, Pengajuan $pengajuan, string $target, Carbon $at): void
    {
        $format = function ($value) {
            if ($value === null || $value === '') {
                return '-';
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }

            return (string) $value;
        };

        $rows = [];
        foreach (['status', 'started_at', 'closed_at', 'confirmed_at', 'rejected_at', 'updated_at'] as $field) {
            $rows[] = [
                $field,
                $before[$field] ?? '-',
                array_key_exists($field, $after) ? $format($after[$field]) : ($before[$field] ?? '-'),
            ];
        }

        $this->line('Pengajuan: '.$pengajuan->nomor.' (id '.$pengajuan->id.')');
        $this->line('Target status: '.$target.' — '.TicketStatus::label($target).' @ '.$at->format('Y-m-d H:i:s'));
        $this->table(['Kolom', 'Sebelum', 'Sesudah'], $rows);
    }
}

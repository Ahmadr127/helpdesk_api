<?php

namespace App\Services\Pengajuan;

use App\Models\Pengajuan;
use App\Models\PengajuanHistori;
use App\Models\User;
use App\Support\TicketStatus;

/**
 * Sumber tunggal transisi status pengajuan (tiket IT & order IPSRS).
 *
 * Dibuat karena logika serupa sebelumnya tersebar di beberapa controller
 * dengan perilaku berbeda: ada yang tidak menulis histori, tidak mengisi
 * started_at/closed_at, atau tidak membersihkan timestamp saat dibuka ulang.
 * Akibatnya status bisa "berubah" di satu layar tapi data/laporan tidak sinkron.
 */
class PengajuanStatusService
{
    public function __construct(
        protected PengajuanWorkflowService $workflow,
        protected PengajuanNotificationService $notificationService,
    ) {}

    /**
     * Ubah status pengajuan dengan timestamp + histori yang konsisten.
     *
     * @param  bool  $notify  Kirim notifikasi status dari service ini. Set false
     *                        bila pemanggil ingin mengirim notifikasinya sendiri
     *                        (mis. API yang memakai label event berbeda).
     * @return array{changed: bool, notified: bool}
     */
    public function change(
        User $actor,
        Pengajuan $pengajuan,
        string $status,
        ?string $notes = null,
        bool $notify = true
    ): array {
        $status = TicketStatus::normalize($status) ?? $status;
        $current = $pengajuan->status;

        if ($status === $current) {
            return ['changed' => false, 'notified' => false];
        }

        if ($status === TicketStatus::DITUTUP
            && in_array($current, [TicketStatus::OPEN, TicketStatus::IN_PROGRESS], true)) {
            // close() mengisi closed_at + histori + notifikasi 'closed'.
            $this->workflow->close($actor, $pengajuan->fresh(), $notes);

            return ['changed' => true, 'notified' => true];
        }

        if ($status === TicketStatus::IN_PROGRESS && $current === TicketStatus::OPEN) {
            // start() mengisi started_at + histori + notifikasi 'started'.
            $this->workflow->start($actor, $pengajuan->fresh(), $notes);

            return ['changed' => true, 'notified' => true];
        }

        $pengajuan->update([
            'status' => $status,
            ...$this->timestampAttributes($pengajuan, $status),
        ]);

        $this->log($actor, $pengajuan, $status, $notes);

        if ($notify) {
            $this->notificationService->notifyStatusChanged($pengajuan, $actor, 'status_updated');

            return ['changed' => true, 'notified' => true];
        }

        return ['changed' => true, 'notified' => false];
    }

    /**
     * Timestamp yang harus ikut diisi/dibersihkan saat status berubah.
     *
     * @return array<string, mixed>
     */
    protected function timestampAttributes(Pengajuan $pengajuan, string $status): array
    {
        return match ($status) {
            TicketStatus::IN_PROGRESS => [
                'started_at' => $pengajuan->started_at ?: now(),
                ...$this->reopenCleanup($pengajuan),
            ],
            TicketStatus::DITUTUP => [
                'closed_at' => $pengajuan->closed_at ?: now(),
                'started_at' => $pengajuan->started_at ?: now(),
                // Menutup (lagi) berarti kembali menunggu konfirmasi user,
                // jadi jejak konfirmasi/penolakan sebelumnya harus bersih.
                'confirmed_at' => null,
                'rejected_at' => null,
                'user_confirmation' => false,
                'user_confirmed_at' => null,
            ],
            TicketStatus::CONFIRMED => [
                'confirmed_at' => $pengajuan->confirmed_at ?: now(),
            ],
            TicketStatus::REJECTED => [
                'rejected_at' => $pengajuan->rejected_at ?: now(),
                // Status ditolak bukan lagi "sudah dikonfirmasi".
                'confirmed_at' => null,
                'user_confirmation' => false,
                'user_confirmed_at' => null,
            ],
            // Kembali ke `open` = buka ulang dari awal.
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
     * Bersihkan jejak penutupan saat pengajuan dibuka kembali (ditutup /
     * confirmed / rejected -> in_progress). Tanpa ini `closed_at` tetap
     * terisi sehingga durasi pengerjaan & filter konfirmasi jadi salah.
     *
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

    protected function log(User $actor, Pengajuan $pengajuan, string $status, ?string $notes = null): void
    {
        $pengajuan->historis()->create([
            'status' => $status,
            'action' => 'status_updated',
            'notes' => $notes ?: 'Status pengajuan diperbarui menjadi '.TicketStatus::label($status).'.',
            'actor_id' => $actor->id,
            'actor_type' => PengajuanHistori::actorTypeFor($actor),
        ]);
    }
}

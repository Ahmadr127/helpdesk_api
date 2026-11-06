<?php

namespace App\Services\Pengajuan;

use App\DTO\Notifications\FirebaseNotificationData;
use App\Facades\FirebaseNotification;
use App\Models\Pengajuan;
use App\Models\PengajuanPesan;
use App\Models\Role;
use App\Models\RoleKategori;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PengajuanNotificationService
{
    public function recipientsFor(Pengajuan $pengajuan, ?User $except = null): Collection
    {
        $roleIds = RoleKategori::where('kategori_id', $pengajuan->kategori_id)
            ->pluck('role_id');

        $roles = Role::whereIn('id', $roleIds)->pluck('slug');

        $users = User::whereIn('role', $roles)
            ->where('status', 1);

        if ($except) {
            $users->where('id', '!=', $except->id);
        }

        return $users->get();
    }

    public function notifyCreated(Pengajuan $pengajuan, User $actor): void
    {
        $recipients = $this->recipientsFor($pengajuan, $actor);

        $jenisCode = $pengajuan->kategori?->jenis?->code ?? 'IT';
        $type = $jenisCode === 'IPSRS' ? 'order_created' : 'ticket_created';
        $title = $jenisCode === 'IPSRS'
            ? "Order Baru {$pengajuan->nomor}"
            : "Tiket Baru {$pengajuan->nomor}";
        $body = $actor->name.': '.mb_strimwidth((string) $pengajuan->deskripsi, 0, 80, '...');

        $this->dispatch($recipients, $title, $body, $this->payload($pengajuan, $type));
    }

    public function notifyStatusChanged(Pengajuan $pengajuan, ?User $actor, string $event): void
    {
        $recipients = $this->recipientsFor($pengajuan, $actor);

        if ($actor === null || (int) $actor->id !== (int) $pengajuan->pemohon_id) {
            $pemohon = $pengajuan->pemohon ?: User::find($pengajuan->pemohon_id);
            if ($pemohon && (int) $pemohon->status === 1 && ! $recipients->contains('id', $pemohon->id)) {
                $recipients->push($pemohon);
            }
        }

        $title = "Update Pengajuan {$pengajuan->nomor}";
        $body = 'Status: '.$pengajuan->status.' oleh '.($actor?->name ?? 'Sistem');

        $this->dispatch($recipients, $title, $body, $this->payload($pengajuan, $event));
    }

    public function notifyMessage(Pengajuan $pengajuan, User $actor, PengajuanPesan $pesan): void
    {
        $recipients = $this->recipientsFor($pengajuan, $actor);

        if ((int) $actor->id !== (int) $pengajuan->pemohon_id) {
            $pemohon = $pengajuan->pemohon ?: User::find($pengajuan->pemohon_id);
            if ($pemohon && (int) $pemohon->status === 1 && ! $recipients->contains('id', $pemohon->id)) {
                $recipients->push($pemohon);
            }
        }

        $title = "Pesan Baru {$pengajuan->nomor}";
        $body = mb_strimwidth((string) $pesan->body, 0, 80, '...');

        if (trim($body) === '') {
            $body = "Pesan baru dari {$actor->name}";
        }

        $this->dispatch($recipients, $title, $body, $this->payload($pengajuan, 'message'));
    }

    protected function dispatch(Collection $recipients, string $title, string $body, array $data): void
    {
        $tokens = $recipients
            ->flatMap(fn (User $user) => $user->getAllFcmTokens())
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            return;
        }

        try {
            $dto = FirebaseNotificationData::make(
                title: $title,
                body: $body,
                data: $data
            );

            FirebaseNotification::queueToTokens($tokens, $dto);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Pengajuan notification dispatch failed: '.$e->getMessage());
        }
    }

    protected function payload(Pengajuan $pengajuan, string $type): array
    {
        return [
            'type' => $type,
            'pengajuan_id' => (string) $pengajuan->id,
            'ticket_id' => $pengajuan->legacy_source === 'ticket' && $pengajuan->legacy_id
                ? (string) $pengajuan->legacy_id
                : '',
            'order_id' => $pengajuan->legacy_source === 'order' && $pengajuan->legacy_id
                ? (string) $pengajuan->legacy_id
                : '',
            'ticket_number' => (string) $pengajuan->nomor,
            'nomor' => (string) $pengajuan->nomor,
            'status' => (string) $pengajuan->status,
            'prioritas' => (string) $pengajuan->prioritas,
            'deep_link' => "helpdesk://pengajuan/{$pengajuan->id}",
            'legacy_id' => $pengajuan->legacy_id ? (string) $pengajuan->legacy_id : '',
        ];
    }
}

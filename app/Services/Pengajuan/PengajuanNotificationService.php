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
    /**
     * Penerima notifikasi untuk sebuah pengajuan.
     *
     * Sumber tunggal: role_kategori — sama dengan PengajuanScopeService.
     * Role yang tidak dipetakan ke kategori pengajuan ini TIDAK menerima
     * notifikasi, walau sedang menjadi PJ aktif, agar scope notifikasi
     * konsisten dengan scope daftar pengajuan.
     */
    public function recipientsFor(Pengajuan $pengajuan, ?User $except = null): Collection
    {
        $roleIds = RoleKategori::where('kategori_id', $pengajuan->kategori_id)
            ->pluck('role_id');

        $roles = Role::whereIn('id', $roleIds)->pluck('slug');

        if ($roles->isEmpty()) {
            return collect();
        }

        $users = User::whereIn('role', $roles)
            ->where('status', 1)
            ->get();

        if ($except) {
            $users = $users->filter(fn (User $u) => (int) $u->id !== (int) $except->id)->values();
        }

        return $users;
    }

    public function notifyAssigned(Pengajuan $pengajuan, array $newUsers, ?User $actor = null): void
    {
        $recipients = collect($newUsers)
            ->filter(fn ($u) => $u instanceof User && (int) $u->status === 1)
            ->filter(fn ($u) => ! $actor || (int) $u->id !== (int) $actor->id)
            ->unique('id')->values();
        if ($recipients->isEmpty()) {
            return;
        }
        $title = "Penugasan {$pengajuan->nomor}";
        $body = 'Anda ditugaskan sebagai penanggung jawab oleh '.($actor?->name ?? 'Admin').'.';

        $payload = array_merge($this->payload($pengajuan, 'assigned'), [
            'title' => $title,
            'message' => $body,
        ]);

        $this->dispatch($recipients, $title, $body, $payload);
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

        $payload = array_merge($this->payload($pengajuan, $type), [
            'title' => $title,
            'message' => $body,
        ]);

        $this->dispatch($recipients, $title, $body, $payload);
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

        $payload = array_merge($this->payload($pengajuan, $event), [
            'title' => $title,
            'message' => $body,
        ]);

        $this->dispatch($recipients, $title, $body, $payload);
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

        $payload = array_merge($this->payload($pengajuan, 'message'), [
            'title' => $title,
            'message' => $body,
        ]);

        $this->dispatch($recipients, $title, $body, $payload);
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

        // In-app Laravel notifications — payload sama dengan FCM, agar /notifications konsisten:
        // recipient, order-of-events, dan field identik untuk web dan mobile.
        try {
            \Illuminate\Support\Facades\Notification::send(
                $recipients,
                new \App\Notifications\PengajuanNotification($data)
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Pengajuan database notification failed: '.$e->getMessage());
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

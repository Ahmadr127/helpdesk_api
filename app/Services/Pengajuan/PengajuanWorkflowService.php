<?php

namespace App\Services\Pengajuan;

use App\Models\Pengajuan;
use App\Models\PengajuanHistori;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PengajuanWorkflowService
{
    public function __construct(
        protected PengajuanScopeService $scopeService,
        protected PengajuanAttachmentService $attachmentService,
        protected PengajuanNotificationService $notificationService,
    ) {
    }

    public function start(User $actor, Pengajuan $pengajuan): Pengajuan
    {
        if ($pengajuan->status !== 'open') {
            throw new \Exception('Pengajuan hanya dapat dimulai saat status open.', 422);
        }

        if (! $this->scopeService->canAct($actor, $pengajuan)) {
            throw new \Exception('Unauthorized', 403);
        }

        $pengajuan = DB::transaction(function () use ($actor, $pengajuan): Pengajuan {
            $pengajuan->update([
                'status' => 'in_progress',
                'started_at' => $pengajuan->started_at ?: now(),
                'updated_by' => $actor->id,
            ]);

            $pengajuan->historis()->create([
                'status' => 'in_progress',
                'action' => 'started',
                'notes' => 'Pengajuan dimulai.',
                'actor_id' => $actor->id,
                'actor_type' => 'user',
            ]);

            return $pengajuan->fresh(['historis']);
        });

        $this->notificationService->notifyStatusChanged($pengajuan, $actor, 'started');

        return $pengajuan;
    }

    public function close(User $actor, Pengajuan $pengajuan, ?string $notes = null): Pengajuan
    {
        if (! in_array($pengajuan->status, ['open', 'in_progress'], true)) {
            throw new \Exception('Pengajuan hanya dapat ditutup saat status open atau in_progress.', 422);
        }

        if (! $this->scopeService->canAct($actor, $pengajuan)) {
            throw new \Exception('Unauthorized', 403);
        }

        return DB::transaction(function () use ($actor, $pengajuan, $notes): Pengajuan {
            $pengajuan->update([
                'status' => 'ditutup',
                'closed_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $pengajuan->historis()->create([
                'status' => 'ditutup',
                'action' => 'closed',
                'notes' => $notes ?: 'Pengajuan ditutup dan menunggu konfirmasi.',
                'actor_id' => $actor->id,
                'actor_type' => 'user',
            ]);

            return $pengajuan->fresh(['historis']);
        });
    }

    public function adminConfirm(User $actor, Pengajuan $pengajuan, ?string $notes = null): Pengajuan
    {
        if ($pengajuan->status !== 'ditutup') {
            throw new \Exception('Pengajuan hanya dapat dikonfirmasi admin saat status ditutup.', 422);
        }

        if (! $this->scopeService->canAct($actor, $pengajuan)) {
            throw new \Exception('Unauthorized', 403);
        }

        return DB::transaction(function () use ($actor, $pengajuan, $notes): Pengajuan {
            $pengajuan->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $pengajuan->historis()->create([
                'status' => 'confirmed',
                'action' => 'admin_confirmed',
                'notes' => $notes ?: 'Pengajuan dikonfirmasi admin.',
                'actor_id' => $actor->id,
                'actor_type' => 'user',
            ]);

            return $pengajuan->fresh(['historis']);
        });
    }

    public function adminReject(User $actor, Pengajuan $pengajuan, ?string $notes = null): Pengajuan
    {
        if (! in_array($pengajuan->status, ['open', 'in_progress', 'ditutup'], true)) {
            throw new \Exception('Pengajuan tidak dapat ditolak pada status ini.', 422);
        }

        if (! $this->scopeService->canAct($actor, $pengajuan)) {
            throw new \Exception('Unauthorized', 403);
        }

        return DB::transaction(function () use ($actor, $pengajuan, $notes): Pengajuan {
            $pengajuan->update([
                'status' => 'rejected',
                'rejected_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $pengajuan->historis()->create([
                'status' => 'rejected',
                'action' => 'admin_rejected',
                'notes' => $notes ?: 'Pengajuan ditolak admin.',
                'actor_id' => $actor->id,
                'actor_type' => 'user',
            ]);

            return $pengajuan->fresh(['historis']);
        });
    }

    public function userConfirm(User $actor, Pengajuan $pengajuan, ?string $notes = null, bool $selesai = true, array $files = []): Pengajuan
    {
        if ((int) $pengajuan->pemohon_id !== (int) $actor->id) {
            throw new \Exception('Unauthorized', 403);
        }

        if ($pengajuan->status !== 'ditutup') {
            throw new \Exception('Pengajuan hanya dapat dikonfirmasi pemilik saat status ditutup.', 422);
        }

        return DB::transaction(function () use ($actor, $pengajuan, $notes, $selesai, $files): Pengajuan {
            if ($selesai) {
                $pengajuan->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'user_confirmation' => true,
                    'user_confirmed_at' => now(),
                    'updated_by' => $actor->id,
                ]);

                $histori = $pengajuan->historis()->create([
                    'status' => 'confirmed',
                    'action' => 'user_confirmed',
                    'notes' => $notes ?: 'Pengajuan dikonfirmasi pemilik.',
                    'actor_id' => $actor->id,
                    'actor_type' => 'user',
                ]);

                $this->attachmentService->store(
                    $pengajuan,
                    $files,
                    'user_confirmation',
                    $actor,
                    $histori
                );

                return $pengajuan->fresh(['historis', 'lampirans']);
            }

            $pengajuan->update([
                'status' => 'in_progress',
                'rejection_count' => (int) $pengajuan->rejection_count + 1,
                'user_confirmation' => false,
                'user_confirmed_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $histori = $pengajuan->historis()->create([
                'status' => 'in_progress',
                'action' => 'user_rejected_confirmation',
                'notes' => $notes ?: 'Pengajuan belum selesai, dikembalikan ke in_progress.',
                'actor_id' => $actor->id,
                'actor_type' => 'user',
            ]);

            $this->attachmentService->store(
                $pengajuan,
                $files,
                'user_response',
                $actor,
                $histori
            );

            return $pengajuan->fresh(['historis', 'lampirans']);
        });
    }

    public function autoConfirmStale(int $hours = 24): int
    {
        $stale = Pengajuan::query()
            ->where('status', 'ditutup')
            ->whereNotNull('closed_at')
            ->where('closed_at', '<=', now()->subHours($hours))
            ->get();

        $confirmed = 0;
        $confirmedItems = [];

        DB::transaction(function () use ($stale, &$confirmed, &$confirmedItems): void {
            foreach ($stale as $pengajuan) {
                if ($pengajuan->status !== 'ditutup') {
                    continue;
                }

                $pengajuan->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'updated_by' => null,
                ]);

                $pengajuan->historis()->create([
                    'status' => 'confirmed',
                    'action' => 'auto_confirmed',
                    'notes' => 'Auto-confirm 24 jam.',
                    'actor_id' => null,
                    'actor_type' => 'system',
                ]);

                $confirmed++;
                $confirmedItems[] = $pengajuan->fresh();
            }
        });

        foreach ($confirmedItems as $pengajuan) {
            try {
                $this->notificationService->notifyStatusChanged($pengajuan, null, 'auto_confirmed');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Auto-confirm notification failed: '.$e->getMessage());
            }
        }

        return $confirmed;
    }
}
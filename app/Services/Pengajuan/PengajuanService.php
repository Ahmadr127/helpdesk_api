<?php

namespace App\Services\Pengajuan;

use App\Models\Department;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\PengajuanHistori;
use App\Models\PengajuanOrderDetail;
use App\Models\PengajuanPesan;
use App\Models\Role;
use App\Models\User;
use App\Services\DepartmentService;
use Illuminate\Support\Facades\DB;

class PengajuanService
{
    public function __construct(
        protected PengajuanNumberService $numberService,
        protected PengajuanAttachmentService $attachmentService,
        protected DepartmentService $departmentService,
        protected PengajuanScopeService $scopeService,
        protected PengajuanNotificationService $notificationService,
        protected PengajuanPenugasanService $penugasanService
    ) {
    }

    public function create(User $actor, array $data, array $files = []): Pengajuan
    {
        $kategori = $this->activeKategori($data['kategori_id'] ?? null);

        $jenisCode = $kategori->jenis->code;
        $departmentId = $this->resolveDepartmentId($actor, $data);
        $department = $departmentId ? Department::with(['location', 'building'])->find($departmentId) : null;

        $lokasiId = $data['lokasi_id'] ?? $department?->location_id;
        $buildingId = $data['building_id'] ?? $department?->building_id;

        $pengajuan = DB::transaction(function () use ($actor, $data, $files, $kategori, $jenisCode, $departmentId, $lokasiId, $buildingId): Pengajuan {
            $nomor = $this->numberService->next($jenisCode);

            $pengajuan = Pengajuan::create([
                'nomor' => $nomor,
                'kategori_id' => $kategori->id,
                'pemohon_id' => $actor->id,
                'pemohon_nama' => $actor->name,
                'department_id' => $departmentId,
                'lokasi_id' => $lokasiId,
                'building_id' => $buildingId,
                'deskripsi' => $data['deskripsi'],
                'prioritas' => $data['prioritas'] ?? 'medium',
                'status' => 'open',
                'follow_up' => $data['follow_up'] ?? null,
                'tanggal' => $data['tanggal'] ?? now(),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $pengajuan->historis()->create([
                'status' => 'open',
                'action' => 'created',
                'notes' => $data['keterangan'] ?? 'Pengajuan dibuat.',
                'actor_id' => $actor->id,
                'actor_type' => 'user',
            ]);

            $this->attachmentService->store(
                $pengajuan,
                $files,
                'initial',
                $actor
            );

            return $pengajuan->fresh(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'historis', 'lampirans']);
        });

        $this->notificationService->notifyCreated($pengajuan, $actor);

        return $pengajuan;
    }

    public function updateOwn(User $actor, Pengajuan $pengajuan, array $data, array $files = []): Pengajuan
    {
        if ((int) $pengajuan->pemohon_id !== (int) $actor->id) {
            throw new \Exception('Unauthorized', 403);
        }

        if ($pengajuan->status !== 'open') {
            throw new \Exception('Pengajuan hanya dapat diubah saat status open.', 422);
        }

        $kategori = $pengajuan->kategori;

        if (array_key_exists('kategori_id', $data) && $data['kategori_id'] !== null) {
            $kategori = $this->activeKategori($data['kategori_id']);

            if ((int) $kategori->jenis_id !== (int) $pengajuan->kategori->jenis_id) {
                throw new \Exception('Kategori tidak boleh berpindah jenis pengajuan.', 422);
            }
        }

        $departmentId = array_key_exists('department_id', $data)
            ? $data['department_id']
            : $pengajuan->department_id;

        $department = $departmentId ? Department::with(['location', 'building'])->find($departmentId) : null;

        $lokasiId = array_key_exists('lokasi_id', $data)
            ? $data['lokasi_id']
            : ($department ? $department->location_id : $pengajuan->lokasi_id);

        $buildingId = array_key_exists('building_id', $data)
            ? $data['building_id']
            : ($department ? $department->building_id : $pengajuan->building_id);

        return DB::transaction(function () use ($actor, $pengajuan, $data, $files, $kategori, $departmentId, $lokasiId, $buildingId): Pengajuan {
            $pengajuan->update([
                'kategori_id' => $kategori->id,
                'department_id' => $departmentId,
                'lokasi_id' => $lokasiId,
                'building_id' => $buildingId,
                'deskripsi' => $data['deskripsi'] ?? $pengajuan->deskripsi,
                'prioritas' => $data['prioritas'] ?? $pengajuan->prioritas,
                'follow_up' => array_key_exists('follow_up', $data) ? $data['follow_up'] : $pengajuan->follow_up,
                'tanggal' => $data['tanggal'] ?? $pengajuan->tanggal,
                'updated_by' => $actor->id,
            ]);

            if (! empty($files)) {
                $pengajuan->lampirans()->where('kind', 'initial')->delete();
                $this->attachmentService->store($pengajuan, $files, 'initial', $actor);
            }

            $pengajuan->historis()->create([
                'status' => $pengajuan->status,
                'action' => 'updated',
                'notes' => $data['keterangan'] ?? 'Pengajuan diperbarui.',
                'actor_id' => $actor->id,
                'actor_type' => 'user',
            ]);

            return $pengajuan->fresh(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'historis', 'lampirans']);
        });
    }

    public function deleteOwn(User $actor, Pengajuan $pengajuan): void
    {
        if ((int) $pengajuan->pemohon_id !== (int) $actor->id) {
            throw new \Exception('Unauthorized', 403);
        }

        if ($pengajuan->status !== 'open') {
            throw new \Exception('Pengajuan hanya dapat dihapus saat status open.', 422);
        }

        DB::transaction(function () use ($actor, $pengajuan): void {
            $pengajuan->historis()->create([
                'status' => $pengajuan->status,
                'action' => 'deleted',
                'notes' => 'Pengajuan dihapus.',
                'actor_id' => $actor->id,
                'actor_type' => 'user',
            ]);

            $pengajuan->delete();
        });
    }

    public function addMessage(User $actor, Pengajuan $pengajuan, string $body, array $files = []): PengajuanPesan
    {
        $senderType = $actor->hasPermission('permission')
            || $actor->hasPermission('dashboard.it')
            || $actor->hasPermission('dashboard.ipsrs')
            ? 'admin'
            : 'user';

        $pesan = DB::transaction(function () use ($actor, $pengajuan, $body, $senderType, $files): PengajuanPesan {
            $pesan = $pengajuan->pesans()->create([
                'sender_id' => $actor->id,
                'sender_type' => $senderType,
                'body' => $body,
            ]);

            $this->attachmentService->store(
                $pengajuan,
                $files,
                $senderType === 'admin' ? 'admin_response' : 'user_response',
                $actor,
                null,
                $pesan
            );

            return $pesan->fresh(['sender', 'lampirans']);
        });

        $this->notificationService->notifyMessage($pengajuan, $actor, $pesan);

        return $pesan;
    }

    public function assign(User $actor, Pengajuan $pengajuan, array $userIds): void
    {
        if (! $this->scopeService->canAct($actor, $pengajuan)) {
            throw new \Exception('Unauthorized', 403);
        }

        DB::transaction(function () use ($actor, $pengajuan, $userIds): void {
            $this->penugasanService->release($pengajuan);

            $this->penugasanService->assign($pengajuan, $userIds, $actor);

            $pengajuan->historis()->create([
                'status' => $pengajuan->status,
                'action' => 'assigned',
                'notes' => 'Penugasan pengajuan diperbarui.',
                'actor_id' => $actor->id,
                'actor_type' => 'user',
                'metadata' => ['user_ids' => array_values(array_map('intval', $userIds))],
            ]);
        });
    }

    protected function activeKategori(int $kategoriId): KategoriPengajuan
    {
        $kategori = KategoriPengajuan::where('id', $kategoriId)
            ->where('status', 1)
            ->first();

        if (! $kategori || ! $kategori->jenis) {
            throw new \Exception('Kategori pengajuan tidak valid atau tidak aktif.', 422);
        }

        return $kategori;
    }

    protected function resolveDepartmentId(User $actor, array $data): ?int
    {
        $departmentId = $data['department_id'] ?? null;

        if ($departmentId) {
            return (int) $departmentId;
        }

        $department = $this->departmentService->resolveForUser($actor, true);

        return $department?->id;
    }
}

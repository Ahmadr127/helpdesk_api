<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanScopeService;
use Illuminate\Http\Request;

class OrderPerbaikanController extends BaseApiController
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanScopeService $scope,
    ) {}

    private function ipsrsQuery($user)
    {
        return Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IPSRS'))
            ->where('pemohon_id', $user->id);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = $this->ipsrsQuery($request->user());

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status === 'ditutup' ? 'ditutup' : $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('nama_barang', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date.' 00:00:00', $request->end_date.' 23:59:59']);
        }

        $paginator = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar order perbaikan',
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kategori_id' => ['required', 'exists:kategori_pengajuan,id'],
            'keluhan' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'location_id' => 'nullable|exists:locations,id',
            'photo' => 'nullable|image|max:5120',
        ]);
        try {
            $data = [
                'kategori_id' => $validated['kategori_id'],
                'deskripsi' => $validated['keluhan'],
                'prioritas' => $validated['priority'],
                'department_id' => $request->user()->department_id,
                'lokasi_id' => $validated['location_id'] ?? null,
            ];
            $pengajuan = $this->service->create($request->user(), $data, $request->file('photo') ? [$request->file('photo')] : []);
            return $this->success($pengajuan, 'Order perbaikan berhasil dibuat', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(Request $request, Pengajuan $orderPerbaikan)
    {
        if ((int) $orderPerbaikan->pemohon_id !== (int) $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }
        $orderPerbaikan->load(['kategori.jenis', 'pemohon', 'historis', 'pesans', 'lampirans', 'orderDetail']);
        return $this->success($orderPerbaikan, 'Detail order');
    }

    public function update(Request $request, Pengajuan $orderPerbaikan)
    {
        if ((int) $orderPerbaikan->pemohon_id !== (int) $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }
        try {
            $validated = $request->validate([
                'keluhan' => 'required|string',
                'priority' => 'required|in:low,medium,high',
            ]);
            $data = ['deskripsi' => $validated['keluhan'], 'prioritas' => $validated['priority']];
            $updated = $this->service->updateOwn($request->user(), $orderPerbaikan, $data, []);
            return $this->success($updated, 'Order berhasil diperbarui');
        } catch (\Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            return $this->error($e->getMessage(), $code);
        }
    }

    public function destroy(Request $request, Pengajuan $orderPerbaikan)
    {
        if ((int) $orderPerbaikan->pemohon_id !== (int) $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }
        try {
            $this->service->deleteOwn($request->user(), $orderPerbaikan);
            return $this->success(null, 'Order berhasil dihapus');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function konfirmasiSelesai(Request $request, Pengajuan $orderPerbaikan)
    {
        $validated = $request->validate([
            'konfirmasi' => 'required|in:selesai,belum',
            'catatan' => 'nullable|string|max:1000',
        ]);
        try {
            $this->service->addMessage($request->user(), $orderPerbaikan, $validated['catatan'] ?? '', []);
            if ($validated['konfirmasi'] === 'selesai') {
                $orderPerbaikan->update(['status' => 'confirmed', 'user_confirmation' => true, 'user_confirmed_at' => now(), 'confirmed_at' => now()]);
            } else {
                $orderPerbaikan->update(['status' => 'in_progress', 'rejection_count' => $orderPerbaikan->rejection_count + 1, 'user_confirmation' => false, 'user_confirmed_at' => now()]);
            }
            return $this->success($orderPerbaikan, $validated['konfirmasi'] === 'selesai' ? 'Terima kasih, order telah dikonfirmasi selesai.' : 'Order dibuka kembali karena belum selesai.');
        } catch (\Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            return $this->error($e->getMessage(), $code);
        }
    }
}

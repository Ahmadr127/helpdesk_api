<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanScopeService;
use App\Services\Pengajuan\PengajuanWorkflowService;
use Illuminate\Http\Request;

class OrderPerbaikanAdminController extends BaseApiController
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanScopeService $scope,
        protected PengajuanWorkflowService $workflow,
    ) {}

    private function ipsrsQuery()
    {
        return Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IPSRS'));
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = $this->ipsrsQuery()->with(['pemohon', 'kategori', 'department', 'lokasi']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('nama_barang', 'like', "%{$search}%")
                    ->orWhere('pemohon_nama', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from') || $request->filled('start_date')) {
            $df = $request->filled('start_date') ? $request->start_date : $request->date_from;
            $query->whereDate('created_at', '>=', $df);
        }
        if ($request->filled('date_to') || $request->filled('end_date')) {
            $dt = $request->filled('end_date') ? $request->end_date : $request->date_to;
            $query->whereDate('created_at', '<=', $dt);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status === 'ditutup' ? 'ditutup' : $request->status);
        }
        if ($request->filled('prioritas')) $query->where('prioritas', $request->prioritas);

        $paginator = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar order perbaikan (administrasi)',
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'statistics' => [
                'totalOrders' => $this->ipsrsQuery()->count(),
                'openOrders' => $this->ipsrsQuery()->where('status', 'open')->count(),
                'inProgressOrders' => $this->ipsrsQuery()->where('status', 'in_progress')->count(),
                'confirmedOrders' => $this->ipsrsQuery()->where('status', 'confirmed')->count(),
                'tutupOrders' => $this->ipsrsQuery()->where('status', 'ditutup')->count(),
                'rejectedOrders' => $this->ipsrsQuery()->where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function show(Pengajuan $orderPerbaikan)
    {
        $orderPerbaikan->load(['pemohon', 'historis', 'pesans', 'lampirans']);
        return $this->success($orderPerbaikan, 'Detail order');
    }

    public function updatePriority(Request $request, Pengajuan $orderPerbaikan)
    {
        try {
            $validated = $request->validate([
                'prioritas' => 'required|in:low,medium,high',
                'priority' => 'nullable|in:low,medium,high',
            ]);
            $prioritas = $validated['prioritas'] ?? $validated['priority'];
            $orderPerbaikan->update(['prioritas' => $prioritas]);
            return $this->success($orderPerbaikan, 'Prioritas order berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function updateStatus(Request $request, Pengajuan $orderPerbaikan)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:in_progress,pending_confirmation,rejected',
                'follow_up' => 'nullable|string',
            ]);
            $orderPerbaikan->update([
                'status' => $validated['status'],
                'follow_up' => $validated['follow_up'] ?? $orderPerbaikan->follow_up,
            ]);
            return $this->success($orderPerbaikan, 'Status berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function confirm(Request $request, Pengajuan $orderPerbaikan)
    {
        try {
            $this->workflow->adminConfirm($request->user(), $orderPerbaikan);
            return $this->success($orderPerbaikan, 'Order dikonfirmasi');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function reject(Request $request, Pengajuan $orderPerbaikan)
    {
        try {
            $this->workflow->adminReject($request->user(), $orderPerbaikan);
            return $this->success($orderPerbaikan, 'Order ditolak');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function start(Request $request, Pengajuan $orderPerbaikan)
    {
        try {
            $this->workflow->start($request->user(), $orderPerbaikan);
            return $this->success($orderPerbaikan, 'Order dimulai');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function inProgress(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $this->ipsrsQuery()->with('pemohon')->where('status', 'in_progress')->latest()->paginate($perPage);
        return response()->json(['success' => true, 'message' => 'In progress orders', 'data' => $paginator->items(), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
    }

    public function confirmed(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $this->ipsrsQuery()->with('pemohon')->where('status', 'confirmed')->latest()->paginate($perPage);
        return response()->json(['success' => true, 'message' => 'Confirmed orders', 'data' => $paginator->items(), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
    }

    public function rejected(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $this->ipsrsQuery()->with('pemohon')->where('status', 'rejected')->latest()->paginate($perPage);
        return response()->json(['success' => true, 'message' => 'Rejected orders', 'data' => $paginator->items(), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
    }

    public function statistics()
    {
        return $this->success([
            'totalOrders' => $this->ipsrsQuery()->count(),
            'openOrders' => $this->ipsrsQuery()->where('status', 'open')->count(),
            'inProgressOrders' => $this->ipsrsQuery()->where('status', 'in_progress')->count(),
            'confirmedOrders' => $this->ipsrsQuery()->where('status', 'confirmed')->count(),
            'tutupOrders' => $this->ipsrsQuery()->where('status', 'ditutup')->count(),
            'rejectedOrders' => $this->ipsrsQuery()->where('status', 'rejected')->count(),
        ], 'Statistics');
    }
}

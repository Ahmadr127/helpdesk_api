<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\PengajuanResource;
use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanNotificationService;
use App\Services\Pengajuan\PengajuanPenugasanService;
use App\Services\Pengajuan\PengajuanScopeService;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanWorkflowService;
use Illuminate\Http\Request;

class OrderPerbaikanAdminController extends BaseApiController
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanScopeService $scope,
        protected PengajuanWorkflowService $workflow,
        protected PengajuanPenugasanService $penugasanService,
        protected PengajuanNotificationService $notificationService,
    ) {}

    private function ipsrsQuery()
    {
        return Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IPSRS'))
            ->with(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user']);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = $this->ipsrsQuery();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
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
        if ($request->filled('priority')) $query->where('prioritas', $request->priority);

        $paginator = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar order perbaikan (administrasi)',
            'data' => PengajuanResource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'statistics' => [
                'totalOrders' => Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IPSRS'))->count(),
                'openOrders' => Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IPSRS'))->where('status', 'open')->count(),
                'inProgressOrders' => Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IPSRS'))->where('status', 'in_progress')->count(),
                'confirmedOrders' => Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IPSRS'))->where('status', 'confirmed')->count(),
                'tutupOrders' => Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IPSRS'))->where('status', 'ditutup')->count(),
                'rejectedOrders' => Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IPSRS'))->where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function show(Pengajuan $orderPerbaikan)
    {
        $orderPerbaikan->load(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user', 'historis', 'pesans.sender', 'lampirans']);
        return $this->success(new PengajuanResource($orderPerbaikan), 'Detail order');
    }

    public function updatePriority(Request $request, Pengajuan $orderPerbaikan)
    {
        try {
            $validated = $request->validate([
                'prioritas' => 'required_without:priority|nullable|in:low,medium,high',
                'priority' => 'required_without:prioritas|nullable|in:low,medium,high',
            ]);
            $prioritas = $validated['prioritas'] ?? $validated['priority'];
            $orderPerbaikan->update(['prioritas' => $prioritas]);
            $orderPerbaikan->load(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user', 'historis', 'pesans.sender', 'lampirans']);
            return $this->success(new PengajuanResource($orderPerbaikan), 'Prioritas order berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function updateStatus(Request $request, Pengajuan $orderPerbaikan)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:in_progress,pending_confirmation,rejected,ditutup,tutup',
                'follow_up' => 'nullable|string',
                'penanggung_jawab_ids' => 'nullable|array',
                'penanggung_jawab_ids.*' => 'integer|exists:users,id',
            ]);
            $status = $validated['status'];
            if ($status === 'tutup') $status = 'ditutup';
            $orderPerbaikan->update([
                'status' => $status,
                'follow_up' => $validated['follow_up'] ?? $orderPerbaikan->follow_up,
            ]);
            $newlyAssigned = [];
            if (array_key_exists('penanggung_jawab_ids', $validated)) {
                $newlyAssigned = $this->penugasanService->sync($orderPerbaikan, $validated['penanggung_jawab_ids'] ?? [], $request->user());
            }
            $orderPerbaikan->refresh();
            $this->notificationService->notifyStatusChanged($orderPerbaikan, $request->user(), 'status_updated');
            if (!empty($newlyAssigned)) {
                $this->notificationService->notifyAssigned($orderPerbaikan, $newlyAssigned, $request->user());
            }
            $orderPerbaikan->load(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user', 'historis', 'pesans.sender', 'lampirans']);
            return $this->success(new PengajuanResource($orderPerbaikan), 'Status berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function updateSubmission(Request $request, Pengajuan $orderPerbaikan)
    {
        try {
            $validated = $request->validate([
                'keluhan' => 'sometimes|required|string',
                'deskripsi' => 'sometimes|required|string',
                'prioritas' => 'nullable|in:low,medium,high',
                'priority' => 'nullable|in:low,medium,high',
                'kategori_id' => 'nullable|integer',
                'kategori_order' => 'nullable|string',
                'lokasi' => 'nullable|integer',
                'location_id' => 'nullable|integer',
                'building_id' => 'nullable|integer',
                'penanggung_jawab_ids' => 'nullable|array',
                'penanggung_jawab_ids.*' => 'integer|exists:users,id',
            ]);
            $payload = [];
            $deskripsi = $validated['keluhan'] ?? $validated['deskripsi'] ?? null;
            if ($deskripsi !== null) $payload['deskripsi'] = $deskripsi;
            $prio = $validated['prioritas'] ?? $validated['priority'] ?? null;
            if ($prio !== null) $payload['prioritas'] = $prio;
            if (array_key_exists('kategori_id', $validated) && $validated['kategori_id']) $payload['kategori_id'] = $validated['kategori_id'];
            $lokasi = $validated['location_id'] ?? $validated['lokasi'] ?? null;
            if ($lokasi !== null) $payload['lokasi_id'] = $lokasi;
            if (array_key_exists('building_id', $validated)) $payload['building_id'] = $validated['building_id'];
            if (!empty($payload)) $orderPerbaikan->update($payload);
            $newlyAssigned = [];
            if (array_key_exists('penanggung_jawab_ids', $validated)) {
                $newlyAssigned = $this->penugasanService->sync($orderPerbaikan, $validated['penanggung_jawab_ids'] ?? [], $request->user());
            }
            $orderPerbaikan->refresh();
            if (!empty($newlyAssigned)) {
                $this->notificationService->notifyAssigned($orderPerbaikan, $newlyAssigned, $request->user());
            }
            $orderPerbaikan->load(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user', 'historis', 'pesans.sender', 'lampirans']);
            return $this->success(new PengajuanResource($orderPerbaikan), 'Pengajuan diperbarui');
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
        $paginator = $this->ipsrsQuery()->where('status', 'in_progress')->latest()->paginate($perPage);
        return response()->json(['success' => true, 'message' => 'In progress orders', 'data' => PengajuanResource::collection($paginator->items())->resolve(), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
    }

    public function confirmed(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $this->ipsrsQuery()->where('status', 'confirmed')->latest()->paginate($perPage);
        return response()->json(['success' => true, 'message' => 'Confirmed orders', 'data' => PengajuanResource::collection($paginator->items())->resolve(), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
    }

    public function rejected(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $this->ipsrsQuery()->where('status', 'rejected')->latest()->paginate($perPage);
        return response()->json(['success' => true, 'message' => 'Rejected orders', 'data' => PengajuanResource::collection($paginator->items())->resolve(), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
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

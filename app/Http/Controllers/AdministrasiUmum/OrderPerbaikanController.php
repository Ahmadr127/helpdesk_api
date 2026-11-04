<?php

namespace App\Http\Controllers\AdministrasiUmum;

use App\Exports\OrderPerbaikanExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OrderPerbaikan\UpdateOrderSubmissionRequest;
use App\Http\Requests\Api\OrderPerbaikan\UpdateStatusRequest;
use App\Models\Building;
use App\Models\Department;
use App\Models\KategoriOrder;
use App\Models\Location;
use App\Models\OrderPerbaikan;
use App\Models\UnitProses;
use App\Services\Api\OrderPerbaikanPjScopeService;
use App\Services\Api\OrderPerbaikanService;
use App\Services\DepartmentService;
use Illuminate\Http\Request;

class OrderPerbaikanController extends Controller
{
    /**
     * View namespace admin (tanpa prefix URL, nama route tetap admin.*).
     */
    private function viewNamespace(?Request $request = null): string
    {
        return 'admin.order-perbaikan';
    }

    public function index(Request $request, OrderPerbaikanPjScopeService $service)
    {
        $filters = $request->only(['search', 'date_from', 'date_to', 'status', 'prioritas', 'location_id']);

        // Default menampilkan order yang masih aktif.
        if (empty($filters['status'])) {
            $filters['status'] = [OrderPerbaikan::STATUS_OPEN, OrderPerbaikan::STATUS_IN_PROGRESS];
        }

        $orders = $service->listForAdmin($filters, 10)->withQueryString();

        // Get only locations that are used in existing orders
        $locationIds = OrderPerbaikan::whereNotNull('lokasi')
            ->distinct()
            ->pluck('lokasi')
            ->toArray();

        $locations = Location::whereIn('id', $locationIds)
            ->orderBy('name')
            ->get();

        return view($this->viewNamespace().'.index', array_merge(
            [
                'orders' => $orders,
                'locations' => $locations,
            ],
            $service->statistics()
        ));
    }

    /**
     * Order milik sendiri (pembuat = user login) di shell admin.
     * Berbeda dari index (semua order, butuh permission `order` + cakupan admin).
     */
    public function myorder(Request $request, OrderPerbaikanPjScopeService $service)
    {
        $filters = $request->only(['search', 'status']);
        $orders = $service->listForUser(auth()->user(), $filters, 10, true)->withQueryString();

        return view('admin.order-perbaikan.myorder.index', compact('orders'));
    }

    /**
     * Detail order milik sendiri (tanpa butuh permission order).
     * Kembalikan JSON bila diminta (untuk modal detail).
     */
    public function showMyorder(Request $request, OrderPerbaikan $orderPerbaikan)
    {
        if ($orderPerbaikan->created_by !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'nomor' => $orderPerbaikan->nomor,
                'status' => $orderPerbaikan->status,
                'kategori_order' => $orderPerbaikan->kategori_order,
                'keluhan' => $orderPerbaikan->keluhan,
                'prioritas' => $orderPerbaikan->prioritas,
                'tanggal' => $orderPerbaikan->tanggal?->format('d/m/Y H:i'),
                'photo_url' => $orderPerbaikan->foto ? \Storage::url($orderPerbaikan->foto) : null,
            ]);
        }

        if ($request->boolean('modal')) {
            $orderPerbaikan->load(['creator', 'history.creator', 'location', 'department']);

            return response(view('admin.order-perbaikan.myorder.component.detail-body', ['order' => $orderPerbaikan])->render());
        }

        $orderPerbaikan->load(['creator', 'history.creator', 'location']);

        // Satu template untuk semua status (open/in_progress/tutup/confirmed/rejected)
        return view($this->viewNamespace().'.show', ['orderPerbaikan' => $orderPerbaikan]);
    }

    /**
     * Form buat order milik sendiri di shell admin.
     */
    public function createMyorder(OrderPerbaikanPjScopeService $service, DepartmentService $departments)
    {
        $nomor = $service->nextNumber();

        $locations = Location::orderBy('name', 'asc')->get();
        $buildings = Building::where('status', 1)->orderBy('name', 'asc')->get();
        $unitProses = UnitProses::where('status', 1)->where('code', '!=', 'SIRS')->get();
        $kategoriOrders = KategoriOrder::where('status', 1)->orderBy('name', 'asc')->get();

        $user = auth()->user();
        $department = $departments->resolveForUser($user);
        $departmentId = $department?->id;
        $departmentLocation = $department?->location;
        $departmentBuilding = $department?->building;
        $unitPengajuCode = $department?->code ?? $user->department ?? 'GENERAL';
        $unitPengajuName = $department?->name ?? Department::where('code', $unitPengajuCode)->value('name') ?? $unitPengajuCode;

        return view('admin.order-perbaikan.myorder.create',
            compact('nomor', 'locations', 'buildings', 'unitProses', 'user', 'unitPengajuCode', 'unitPengajuName', 'kategoriOrders', 'departmentId', 'departmentLocation', 'departmentBuilding'));
    }

    /**
     * Simpan order milik sendiri dari shell admin.
     */
    public function storeMyorder(\App\Http\Requests\Api\OrderPerbaikan\StoreOrderPerbaikanRequest $request, OrderPerbaikanPjScopeService $service)
    {
        $validated = $request->validated();

        try {
            $orderPerbaikan = $service->create(auth()->user(), $validated, $request->file('foto'), 'Web');

            return redirect()
                ->route('admin.order-perbaikan.myorder')
                ->with('success', 'Order perbaikan berhasil dibuat dengan nomor: '.$orderPerbaikan->nomor);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat order: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Form ubah order milik sendiri (hanya saat open).
     */
    public function editMyorder(OrderPerbaikan $orderPerbaikan, DepartmentService $departments)
    {
        if ($orderPerbaikan->created_by !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        if ($orderPerbaikan->status !== 'open') {
            return redirect()->route('admin.order-perbaikan.myorder.show', $orderPerbaikan)
                ->with('error', 'Hanya order dengan status open yang dapat diedit.');
        }

        $locations = Location::orderBy('name', 'asc')->get();
        $kategoriOrders = KategoriOrder::where('status', 1)->orderBy('name', 'asc')->get();
        $department = $departments->resolveForUser(auth()->user());
        $departmentId = $department?->id;
        $departmentLocation = $department?->location;
        $departmentBuilding = $department?->building;

        return view('admin.order-perbaikan.myorder.edit', compact('orderPerbaikan', 'locations', 'kategoriOrders', 'departmentId', 'departmentLocation', 'departmentBuilding'));
    }

    /**
     * Update order milik sendiri.
     */
    public function updateMyorder(\App\Http\Requests\Api\OrderPerbaikan\UpdateOrderPerbaikanRequest $request, OrderPerbaikan $orderPerbaikan, OrderPerbaikanPjScopeService $service)
    {
        $validated = $request->validated();

        try {
            $service->update(auth()->user(), $orderPerbaikan, $validated, $request->file('foto'), 'Web');

            return redirect()
                ->route('admin.order-perbaikan.myorder.show', $orderPerbaikan)
                ->with('success', 'Order perbaikan berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Hapus order milik sendiri (hanya saat open).
     */
    public function destroyMyorder(OrderPerbaikan $orderPerbaikan, OrderPerbaikanPjScopeService $service)
    {
        try {
            $service->delete(auth()->user(), $orderPerbaikan);

            return redirect()->route('admin.order-perbaikan.myorder')
                ->with('success', 'Order perbaikan berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function filterOrders($type, $value, OrderPerbaikanPjScopeService $service)
    {
        $filters = [];

        if ($type === 'status') {
            $filters['status'] = $value;
        } elseif ($type === 'prioritas') {
            $filters['prioritas'] = $value;
        }

        $orders = $service->listForAdmin($filters, 10)->withQueryString();

        return view($this->viewNamespace().'.index', array_merge(
            ['orders' => $orders],
            $service->statistics()
        ));
    }

    public function inProgress(Request $request, OrderPerbaikanPjScopeService $service)
    {
        $filters = array_merge(
            $request->only(['search', 'prioritas', 'location_id']),
            ['status' => OrderPerbaikan::STATUS_IN_PROGRESS]
        );

        $orders = $service->listForAdmin($filters, 10)->withQueryString();
        $inProgressOrders = OrderPerbaikan::where('status', 'in_progress')->count();

        // Get only locations that are used in existing in-progress orders
        $locationIds = OrderPerbaikan::where('status', 'in_progress')
            ->whereNotNull('lokasi')
            ->distinct()
            ->pluck('lokasi')
            ->toArray();

        $locations = Location::whereIn('id', $locationIds)
            ->orderBy('name')
            ->get();

        if ($request->ajax()) {
            return view($this->viewNamespace().'.in-progress', [
                'orders' => $orders,
                'inProgressOrders' => $inProgressOrders,
                'locations' => $locations,
            ])->render();
        }

        return view($this->viewNamespace().'.in-progress', [
            'orders' => $orders,
            'inProgressOrders' => $inProgressOrders,
            'locations' => $locations,
        ]);
    }

    public function confirmed(Request $request, OrderPerbaikanPjScopeService $service)
    {
        $filters = array_merge(
            $request->only(['search', 'date_from', 'date_to']),
            ['status' => OrderPerbaikan::STATUS_CONFIRMED]
        );

        $orders = $service->listForAdmin($filters, 10)->withQueryString();

        if ($request->ajax()) {
            return view($this->viewNamespace().'.confirmed', [
                'orders' => $orders,
            ])->render();
        }

        return view($this->viewNamespace().'.confirmed', array_merge(
            ['orders' => $orders],
            $service->statistics()
        ));
    }

    public function rejected(Request $request, OrderPerbaikanPjScopeService $service)
    {
        $filters = array_merge(
            $request->only(['search', 'date_from', 'date_to']),
            ['status' => OrderPerbaikan::STATUS_REJECTED]
        );

        $orders = $service->listForAdmin($filters, 10)->withQueryString();

        if ($request->ajax()) {
            return view($this->viewNamespace().'.rejected', [
                'orders' => $orders,
            ])->render();
        }

        return view($this->viewNamespace().'.rejected', array_merge(
            ['orders' => $orders],
            $service->statistics()
        ));
    }

    public function rendah()
    {
        return $this->filterOrders('prioritas', 'RENDAH');
    }

    public function sedang()
    {
        return $this->filterOrders('prioritas', 'SEDANG');
    }

    public function tinggi()
    {
        return $this->filterOrders('prioritas', 'TINGGI/URGENT');
    }

    public function show(OrderPerbaikan $orderPerbaikan)
    {
        $orderPerbaikan->load(['creator', 'history.creator', 'location', 'department', 'penanggungJawab']);

        $locations = Location::orderBy('name', 'asc')->get();
        $kategoriOrders = KategoriOrder::where('status', 1)->orderBy('name', 'asc')->get();
        // Picker PJ menampilkan semua user aktif; assignment kategori hanya
        // menentukan PJ default order (tidak membatasi daftar).
        $users = \App\Models\User::where('status', 1)->orderBy('name')->get();

        $selectedPjIds = $orderPerbaikan->penanggungJawab->pluck('id')->toArray();
        if (empty($selectedPjIds)) {
            $selectedPjIds = [auth()->id()];
        }

        // Satu template untuk semua status (open/in_progress/tutup/confirmed/rejected)
        return view($this->viewNamespace().'.show', compact('orderPerbaikan', 'locations', 'kategoriOrders', 'users', 'selectedPjIds'));
    }

    public function updateSubmission(UpdateOrderSubmissionRequest $request, OrderPerbaikan $orderPerbaikan, OrderPerbaikanPjScopeService $service)
    {
        $validated = $request->validated();

        try {
            $service->updateAdminOrder(auth()->user(), $orderPerbaikan, $validated, $request->file('foto'), $request->input('penanggung_jawab_ids', []));

            return redirect()->route('admin.order-perbaikan.show', $orderPerbaikan)
                ->with('success', 'Pengajuan order berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui pengajuan: '.$e->getMessage())->withInput();
        }
    }

    public function updateStatus(UpdateStatusRequest $request, OrderPerbaikan $orderPerbaikan, OrderPerbaikanPjScopeService $service)
    {
        $validated = $request->validated();
        if ($request->hasFile('lampiran')) {
            $validated['lampiran'] = $service->storeLampiran($request->file('lampiran'));
        }

        try {
            $data = [
                'status' => $validated['status'],
                'follow_up' => $validated['follow_up'],
                'prioritas' => $validated['prioritas'] ?? null,
                'lampiran' => $validated['lampiran'] ?? null,
            ];

            $service->updateStatus(auth()->user(), $orderPerbaikan, $data);

            return redirect()
                ->route('admin.order-perbaikan.show', $orderPerbaikan)
                ->with('success', 'Status order berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui status: '.$e->getMessage())->withInput();
        }
    }

    public function confirm(OrderPerbaikan $orderPerbaikan, OrderPerbaikanPjScopeService $service)
    {
        try {
            $service->confirm(auth()->user(), $orderPerbaikan, 'Web');

            return redirect()->route('admin.order-perbaikan.show', $orderPerbaikan)->with('success', 'Order berhasil dikonfirmasi.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan saat mengkonfirmasi order: '.$e->getMessage());
        }
    }

    public function reject(OrderPerbaikan $orderPerbaikan, OrderPerbaikanPjScopeService $service)
    {
        try {
            $service->reject(auth()->user(), $orderPerbaikan, 'Web');

            return redirect()->route('admin.order-perbaikan.show', $orderPerbaikan)->with('success', 'Order berhasil ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan saat menolak order: '.$e->getMessage());
        }
    }

    public function complete(Request $request, OrderPerbaikan $orderPerbaikan, OrderPerbaikanPjScopeService $service)
    {
        try {
            // Alur baru: "Selesai" dari admin berarti menutup order untuk menunggu konfirmasi user.
            $service->updateStatus(auth()->user(), $orderPerbaikan, [
                'status' => OrderPerbaikan::STATUS_TUTUP,
                'follow_up' => $request->follow_up ?? 'Pengerjaan selesai, menunggu konfirmasi user.',
            ]);

            return redirect()
                ->route('admin.order-perbaikan.show', $orderPerbaikan)
                ->with('success', 'Order ditutup dan menunggu konfirmasi selesai dari user.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menutup order: '.$e->getMessage());
        }
    }

    public function start(OrderPerbaikan $orderPerbaikan, OrderPerbaikanPjScopeService $service)
    {
        try {
            $service->start(auth()->user(), $orderPerbaikan, 'Web');

            return redirect()->back()->with('success', 'Order berhasil dimulai.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function total(Request $request, OrderPerbaikanPjScopeService $service)
    {
        $filters = $request->only(['search', 'status', 'date_from', 'date_to']);
        $orders = $service->listForAdmin($filters, 10)->withQueryString();

        if ($request->ajax()) {
            return view($this->viewNamespace().'.total', [
                'orders' => $orders,
            ])->render();
        }

        return view($this->viewNamespace().'.total', array_merge(
            ['orders' => $orders],
            $service->statistics()
        ));
    }

    public function export(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'status' => 'nullable|string|in:open,in_progress,completed,confirmed,rejected',
                'selected_ids' => 'nullable|string',
            ]);

            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $status = $request->input('status', 'confirmed');

            // Handle selected IDs if provided
            $selectedIds = [];
            if ($request->filled('selected_ids')) {
                $selectedIds = array_filter(
                    explode(',', $request->input('selected_ids')),
                    function ($id) {
                        return is_numeric($id) && intval($id) > 0;
                    }
                );

                if (empty($selectedIds)) {
                    return back()->with('error', 'ID yang dipilih tidak valid.');
                }

                $selectedIds = array_map('intval', $selectedIds);

                // Verify the IDs exist in the database
                $existingIds = OrderPerbaikan::whereIn('id', $selectedIds)->pluck('id')->toArray();
                if (count($existingIds) !== count($selectedIds)) {
                    return back()->with('error', 'Beberapa ID yang dipilih tidak ditemukan.');
                }
            }

            // Build query to check data availability
            $query = OrderPerbaikan::query();
            if (! empty($selectedIds)) {
                $query->whereIn('id', $selectedIds);
            } else {
                if ($dateFrom) {
                    $query->whereDate('created_at', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('created_at', '<=', $dateTo);
                }
                $query->where('status', $status);
            }

            // Check if we have any data to export
            $count = $query->count();
            if ($count === 0) {
                return back()->with('error', 'Tidak ada data yang dapat diekspor dengan filter yang dipilih.');
            }

            // Log the export attempt
            \Log::info('Attempting export', [
                'user_id' => auth()->id(),
                'selected_ids' => $selectedIds,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $status,
                'count' => $count,
            ]);

            $export = new OrderPerbaikanExport($dateFrom, $dateTo, $selectedIds, $status);

            return $export->download('order_perbaikan_'.now()->format('Y-m-d_His').'.xlsx');

        } catch (\Exception $e) {
            \Log::error('Export error: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'selected_ids' => $request->input('selected_ids'),
                'date_from' => $dateFrom ?? null,
                'date_to' => $dateTo ?? null,
                'status' => $status ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Terjadi kesalahan saat mengekspor data: '.$e->getMessage());
        }
    }
}

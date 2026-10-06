<?php

namespace App\Http\Controllers\AdministrasiUmum;

use App\Exports\OrderPerbaikanExport;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\KategoriOrder;
use App\Models\Location;
use App\Models\OrderPerbaikan;
use App\Models\UnitProses;
use App\Services\Api\OrderPerbaikanService;
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

    private function getStatistics()
    {
        return [
            'totalOrders' => OrderPerbaikan::count(),
            'openOrders' => OrderPerbaikan::where('status', 'open')->count(),
            'inProgressOrders' => OrderPerbaikan::where('status', 'in_progress')->count(),
            'confirmedOrders' => OrderPerbaikan::where('status', 'confirmed')->count(),
            'tutupOrders' => OrderPerbaikan::where('status', 'tutup')->count(),
            'rejectedOrders' => OrderPerbaikan::where('status', 'rejected')->count(),
            'rendahOrders' => OrderPerbaikan::where('prioritas', 'RENDAH')->count(),
            'sedangOrders' => OrderPerbaikan::where('prioritas', 'SEDANG')->count(),
            'tinggiOrders' => OrderPerbaikan::where('prioritas', 'TINGGI/URGENT')->count(),
        ];
    }

    public function index(Request $request)
    {
        $query = OrderPerbaikan::with(['creator', 'history', 'location']);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('nama_barang', 'like', "%{$search}%")
                    ->orWhere('nama_peminta', 'like', "%{$search}%");
            });
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('tanggal', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal', '<=', $request->date_to);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to showing only open and in_progress orders when no status filter
            $query->whereIn('status', ['open', 'in_progress']);
        }

        // Apply priority filter
        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }

        // Apply location filter
        if ($request->filled('location_id')) {
            $query->where('lokasi', $request->location_id);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

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
            $this->getStatistics()
        ));
    }

    /**
     * Resolve departemen user (utamakan department_id, fallback lookup string).
     */
    protected function resolveDepartment($user): ?Department
    {
        if (! empty($user->department_id)) {
            $dept = Department::with(['location', 'building'])->find($user->department_id);
            if ($dept) {
                return $dept;
            }
        }
        if ($user->department) {
            $deptValue = $user->department;

            return Department::with(['location', 'building'])->where('code', $deptValue)->first()
                ?? Department::with(['location', 'building'])->where('name', $deptValue)->first()
                ?? Department::with(['location', 'building'])->whereRaw('LOWER(code) = LOWER(?)', [$deptValue])->first()
                ?? Department::with(['location', 'building'])->whereRaw('LOWER(name) = LOWER(?)', [$deptValue])->first();
        }

        return null;
    }

    /**
     * Order milik sendiri (pembuat = user login) di shell admin.
     * Berbeda dari index (semua order, butuh permission `order` + cakupan admin).
     */
    public function myorder(Request $request)
    {
        $query = OrderPerbaikan::with(['creator', 'location'])
            ->where('created_by', auth()->id());

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('nama_barang', 'like', "%{$search}%")
                    ->orWhere('keluhan', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

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
                'nama_barang' => $orderPerbaikan->nama_barang,
                'kategori_order' => $orderPerbaikan->kategori_order,
                'keluhan' => $orderPerbaikan->keluhan,
                'prioritas' => $orderPerbaikan->prioritas,
                'tanggal' => $orderPerbaikan->tanggal?->format('d/m/Y H:i'),
                'photo_url' => $orderPerbaikan->foto ? \Storage::url($orderPerbaikan->foto) : null,
            ]);
        }

        if ($request->boolean('modal')) {
            $orderPerbaikan->load(['creator', 'history.creator', 'location', 'unitProses', 'department']);

            return response(view('admin.order-perbaikan.myorder.component.detail-body', ['order' => $orderPerbaikan])->render());
        }

        $orderPerbaikan->load(['creator', 'history.creator', 'location']);

        switch ($orderPerbaikan->status) {
            case 'confirmed':
                return view($this->viewNamespace().'.detail-confirmed', ['order' => $orderPerbaikan]);
            case 'rejected':
                return view($this->viewNamespace().'.detail-rejected', ['order' => $orderPerbaikan]);
            default:
                return view($this->viewNamespace().'.show', ['orderPerbaikan' => $orderPerbaikan]);
        }
    }

    /**
     * Form buat order milik sendiri di shell admin.
     */
    public function createMyorder()
    {
        $currentDate = now();
        $prefix = 'OP/RTG/MTC-'.$currentDate->format('Ymd');
        $lastOrder = OrderPerbaikan::withTrashed()
            ->where('nomor', 'like', $prefix.'%')
            ->orderBy('nomor', 'desc')
            ->first();
        $nomor = $prefix.($lastOrder ? str_pad(((int) substr($lastOrder->nomor, -3)) + 1, 3, '0', STR_PAD_LEFT) : '001');

        $locations = Location::orderBy('name', 'asc')->get();
        $unitProses = UnitProses::where('status', 1)->where('code', '!=', 'SIRS')->get();
        $kategoriOrders = KategoriOrder::where('status', 1)->orderBy('name', 'asc')->get();

        $user = auth()->user();
        $department = $this->resolveDepartment($user);
        $departmentId = $department?->id;
        $departmentLocation = $department?->location;
        $departmentBuilding = $department?->building;
        $unitPengajuCode = $department?->code ?? $user->department ?? 'GENERAL';
        $unitPengajuName = $department?->name ?? Department::where('code', $unitPengajuCode)->value('name') ?? $unitPengajuCode;

        return view('admin.order-perbaikan.myorder.create',
            compact('nomor', 'locations', 'unitProses', 'user', 'unitPengajuCode', 'unitPengajuName', 'kategoriOrders', 'departmentId', 'departmentLocation', 'departmentBuilding'));
    }

    /**
     * Simpan order milik sendiri dari shell admin.
     */
    public function storeMyorder(Request $request, OrderPerbaikanService $service)
    {
        $validated = $request->validate([
            'unit_proses_code' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
            'kode_inventaris' => 'nullable|string',
            'nama_barang' => 'nullable|string',
            'kategori_order' => 'nullable|string',
            'lokasi' => 'nullable|exists:locations,id',
            'keluhan' => 'required|string',
            'prioritas' => 'required|in:RENDAH,SEDANG,TINGGI/URGENT',
            'tanggal' => 'required|date',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

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
    public function editMyorder(OrderPerbaikan $orderPerbaikan)
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
        $department = $this->resolveDepartment(auth()->user());
        $departmentId = $department?->id;
        $departmentLocation = $department?->location;
        $departmentBuilding = $department?->building;

        return view('admin.order-perbaikan.myorder.edit', compact('orderPerbaikan', 'locations', 'kategoriOrders', 'departmentId', 'departmentLocation', 'departmentBuilding'));
    }

    /**
     * Update order milik sendiri.
     */
    public function updateMyorder(Request $request, OrderPerbaikan $orderPerbaikan, OrderPerbaikanService $service)
    {
        $validated = $request->validate([
            'kode_inventaris' => 'nullable|string',
            'nama_barang' => 'nullable|string',
            'kategori_order' => 'nullable|string',
            'lokasi' => 'nullable|exists:locations,id',
            'keluhan' => 'required|string',
            'prioritas' => 'required|in:RENDAH,SEDANG,TINGGI/URGENT',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

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
    public function destroyMyorder(OrderPerbaikan $orderPerbaikan, OrderPerbaikanService $service)
    {
        try {
            $service->delete(auth()->user(), $orderPerbaikan);

            return redirect()->route('admin.order-perbaikan.myorder')
                ->with('success', 'Order perbaikan berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function filterOrders($type, $value)
    {
        $query = OrderPerbaikan::with(['creator', 'history']);

        switch ($type) {
            case 'status':
                $query->where('status', $value);
                break;
            case 'priority':
                $query->where('prioritas', $value);
                break;
                // Add more filter types as needed
        }

        $orders = $query->latest()->paginate(10);

        return view($this->viewNamespace().'.index', array_merge(
            ['orders' => $orders],
            $this->getStatistics()
        ));
    }

    public function inProgress(Request $request)
    {
        $query = OrderPerbaikan::with(['creator', 'location'])
            ->where('status', 'in_progress');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('nama_barang', 'like', "%{$search}%")
                    ->orWhere('nama_peminta', 'like', "%{$search}%")
                    ->orWhere('keluhan', 'like', "%{$search}%")
                    ->orWhere('kode_inventaris', 'like', "%{$search}%");
            });
        }

        // Apply priority filter
        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }

        // Apply location filter
        if ($request->filled('location_id')) {
            $query->where('lokasi', $request->location_id);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();
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

    public function confirmed(Request $request)
    {
        $query = OrderPerbaikan::with(['creator', 'history'])
            ->where('status', 'confirmed');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('nama_barang', 'like', "%{$search}%")
                    ->orWhere('nama_peminta', 'like', "%{$search}%")
                    ->orWhere('keluhan', 'like', "%{$search}%");
            });
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view($this->viewNamespace().'.confirmed', [
                'orders' => $orders,
            ])->render();
        }

        return view($this->viewNamespace().'.confirmed', array_merge(
            ['orders' => $orders],
            $this->getStatistics()
        ));
    }

    public function rejected(Request $request)
    {
        $query = OrderPerbaikan::with(['creator', 'history'])
            ->where('status', 'rejected');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('nama_barang', 'like', "%{$search}%")
                    ->orWhere('nama_peminta', 'like', "%{$search}%")
                    ->orWhere('keluhan', 'like', "%{$search}%");
            });
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view($this->viewNamespace().'.rejected', [
                'orders' => $orders,
            ])->render();
        }

        return view($this->viewNamespace().'.rejected', array_merge(
            ['orders' => $orders],
            $this->getStatistics()
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
        $orderPerbaikan->load(['creator', 'history.creator', 'location']);

        // Direct to specific view based on status
        switch ($orderPerbaikan->status) {
            case 'confirmed':
                return view($this->viewNamespace().'.detail-confirmed', ['order' => $orderPerbaikan]);
            case 'rejected':
                return view($this->viewNamespace().'.detail-rejected', ['order' => $orderPerbaikan]);
            default:
                return view($this->viewNamespace().'.show', ['orderPerbaikan' => $orderPerbaikan]);
        }
    }

    public function updateStatus(Request $request, OrderPerbaikan $orderPerbaikan, OrderPerbaikanService $service)
    {
        $validated = $request->validate([
            'status' => 'required|in:in_progress,tutup,rejected',
            'follow_up' => 'required|string',
            'prioritas' => 'sometimes|required|in:RENDAH,SEDANG,TINGGI/URGENT',
            'nama_penanggung_jawab' => 'nullable|string',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        try {
            $data = [
                'status' => $validated['status'],
                'follow_up' => $validated['follow_up'],
                'prioritas' => $validated['prioritas'] ?? null,
                'nama_penanggung_jawab' => $validated['nama_penanggung_jawab'] ?? $request->input('nama_penanggung_jawab'),
            ];

            if ($request->hasFile('lampiran')) {
                $file = $request->file('lampiran');
                $filename = 'lampiran_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('order-lampiran', $filename, 'public');
                $data['lampiran'] = $path;
            }

            $service->updateStatus(auth()->user(), $orderPerbaikan, $data);

            return redirect()
                ->route('admin.order-perbaikan.show', $orderPerbaikan)
                ->with('success', 'Status order berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memperbarui status: '.$e->getMessage())->withInput();
        }
    }

    public function confirm(OrderPerbaikan $orderPerbaikan, OrderPerbaikanService $service)
    {
        try {
            $service->confirm(auth()->user(), $orderPerbaikan, 'Web');

            return redirect()->route('admin.order-perbaikan.show', $orderPerbaikan)->with('success', 'Order berhasil dikonfirmasi.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan saat mengkonfirmasi order: '.$e->getMessage());
        }
    }

    public function reject(OrderPerbaikan $orderPerbaikan, OrderPerbaikanService $service)
    {
        try {
            $service->reject(auth()->user(), $orderPerbaikan, 'Web');

            return redirect()->route('admin.order-perbaikan.show', $orderPerbaikan)->with('success', 'Order berhasil ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan saat menolak order: '.$e->getMessage());
        }
    }

    public function complete(Request $request, OrderPerbaikan $orderPerbaikan, OrderPerbaikanService $service)
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

    public function start(OrderPerbaikan $orderPerbaikan, OrderPerbaikanService $service)
    {
        try {
            $service->start(auth()->user(), $orderPerbaikan, 'Web');

            return redirect()->back()->with('success', 'Order berhasil dimulai.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function total(Request $request)
    {
        $query = OrderPerbaikan::with(['creator', 'history']);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('nama_barang', 'like', "%{$search}%")
                    ->orWhere('nama_peminta', 'like', "%{$search}%")
                    ->orWhere('keluhan', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view($this->viewNamespace().'.total', [
                'orders' => $orders,
            ])->render();
        }

        return view($this->viewNamespace().'.total', array_merge(
            ['orders' => $orders],
            $this->getStatistics()
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

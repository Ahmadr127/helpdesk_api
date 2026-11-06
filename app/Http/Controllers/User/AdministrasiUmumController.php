<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanScopeService;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanWorkflowService;
use Illuminate\Http\Request;

class AdministrasiUmumController extends Controller
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanWorkflowService $workflow,
        protected PengajuanScopeService $scope,
    ) {}

    private function ipsrsQuery()
    {
        return Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IPSRS'));
    }

    public function indexOrderPerbaikan(Request $request)
    {
        $status = $request->status ?? 'all';

        $query = $this->ipsrsQuery()
            ->where('pemohon_id', auth()->id());

        if ($status !== 'all') {
            $query->where('status', $status === 'tutup' ? 'pending_confirmation' : $status);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%")
                    ->orWhere('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_inventaris', 'like', "%{$search}%");
            });
        }

        if ($request->kategori_order) {
            $query->whereHas('kategori', fn ($q) => $q->where('name', $request->kategori_order));
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('tanggal', [
                $request->start_date.' 00:00:00',
                $request->end_date.' 23:59:59',
            ]);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        $locations = \App\Models\Location::all();
        $unitProses = \App\Models\UnitProses::where('status', 1)->where('code', '!=', 'SIRS')->get();
        $kategoriOrders = KategoriPengajuan::where('status', 1)->whereHas('jenis', fn ($q) => $q->where('code', 'IPSRS'))->orderBy('name')->get();

        $stats = [
            'total' => $this->ipsrsQuery()->where('pemohon_id', auth()->id())->count(),
            'in_progress' => $this->ipsrsQuery()->where('pemohon_id', auth()->id())->where('status', 'in_progress')->count(),
            'tutup' => $this->ipsrsQuery()->where('pemohon_id', auth()->id())->where('status', 'pending_confirmation')->count(),
            'confirmed' => $this->ipsrsQuery()->where('pemohon_id', auth()->id())->where('status', 'confirmed')->count(),
            'rejected' => $this->ipsrsQuery()->where('pemohon_id', auth()->id())->where('status', 'rejected')->count(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('user.administrasi-umum.order-perbaikan._table', compact('orders'))->render(),
            ]);
        }

        return view('user.administrasi-umum.order-perbaikan.index', compact('orders', 'locations', 'unitProses', 'status', 'stats', 'kategoriOrders'));
    }

    public function createOrderPerbaikan()
    {
        $user = auth()->user();
        if (! $user->department && ! $user->department_id) {
            return redirect()->route('user.settings')
                ->with('error', 'Mohon lengkapi data departemen Anda terlebih dahulu.');
        }

        $locations = \App\Models\Location::orderBy('name', 'asc')->get();
        $unitProses = \App\Models\UnitProses::where('status', 1)->where('code', '!=', 'SIRS')->get();
        $kategoriOrders = KategoriPengajuan::where('status', 1)->whereHas('jenis', fn ($q) => $q->where('code', 'IPSRS'))->orderBy('name')->get();

        $department = null;
        if (! empty($user->department_id)) {
            $department = \App\Models\Department::with(['location', 'building'])->find($user->department_id);
        }
        $departmentId = $department?->id;
        $departmentLocation = $department?->location;
        $departmentBuilding = $department?->building;

        $unitPengajuCode = $department?->code ?? $user->department ?? 'GENERAL';
        $unitPengajuName = $department?->name ?? \App\Models\Department::where('code', $unitPengajuCode)->value('name') ?? $unitPengajuCode;

        $prefix = 'OP/RTG/MTC-'.now()->format('Ymd');
        $lastOrder = Pengajuan::where('nomor', 'like', $prefix.'%')->orderBy('nomor', 'desc')->first();
        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->nomor, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }
        $nomor = $prefix.$newNumber;

        return view('user.administrasi-umum.order-perbaikan.create', compact('nomor', 'locations', 'unitProses', 'user', 'unitPengajuCode', 'unitPengajuName', 'kategoriOrders', 'departmentId', 'departmentLocation', 'departmentBuilding'));
    }

    public function storeOrderPerbaikan(Request $request)
    {
        $user = auth()->user();
        if (! $user->department && ! $user->department_id) {
            return redirect()->route('user.settings')
                ->with('error', 'Mohon lengkapi data departemen Anda terlebih dahulu.');
        }

        $validated = $request->validate([
            'kategori_id' => ['required', 'exists:kategori_pengajuan,id'],
            'location_id' => 'nullable|exists:locations,id',
            'building_id' => 'nullable|exists:buildings,id',
            'keluhan' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'unit_penerima' => 'nullable|string',
            'kode_inventaris' => 'nullable|string',
            'nama_barang' => 'nullable|string',
            'unit_proses_id' => 'nullable|exists:unit_proses,id',
            'photo' => 'nullable|image|max:5120',
        ]);

        $data = [
            'kategori_id' => $validated['kategori_id'],
            'lokasi_id' => $validated['location_id'] ?? null,
            'building_id' => $validated['building_id'] ?? null,
            'deskripsi' => $validated['keluhan'],
            'prioritas' => $validated['priority'],
            'department_id' => $user->department_id,
            'unit_penerima' => $validated['unit_penerima'] ?? null,
            'kode_inventaris' => $validated['kode_inventaris'] ?? null,
            'nama_barang' => $validated['nama_barang'] ?? null,
            'unit_proses_id' => $validated['unit_proses_id'] ?? null,
        ];

        $files = $request->file('photo') ? [$request->file('photo')] : [];
        $pengajuan = $this->service->create($user, $data, $files);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Order perbaikan berhasil dibuat dengan nomor: '.$pengajuan->nomor,
                'data' => $pengajuan,
            ]);
        }

        return redirect()
            ->route('user.administrasi-umum.order-perbaikan.show', $pengajuan)
            ->with('success', 'Order perbaikan berhasil dibuat dengan nomor: '.$pengajuan->nomor);
    }

    public function showOrderPerbaikan(Pengajuan $orderPerbaikan)
    {
        if ((int) $orderPerbaikan->pemohon_id !== (int) auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        $orderPerbaikan->load(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'orderDetail', 'historis.actor', 'pesans.sender', 'lampirans', 'penugasan.user']);

        return view('user.administrasi-umum.order-perbaikan.show', compact('orderPerbaikan'));
    }

    public function editOrderPerbaikan(Pengajuan $orderPerbaikan)
    {
        if ((int) $orderPerbaikan->pemohon_id !== (int) auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        if ($orderPerbaikan->status !== 'open') {
            return redirect()->route('user.administrasi-umum.order-perbaikan.show', $orderPerbaikan)
                ->with('error', 'Hanya order dengan status open yang dapat diedit.');
        }

        $locations = \App\Models\Location::orderBy('name', 'asc')->get();
        $kategoriOrders = KategoriPengajuan::where('status', 1)->whereHas('jenis', fn ($q) => $q->where('code', 'IPSRS'))->orderBy('name')->get();

        return view('user.administrasi-umum.order-perbaikan.edit', compact('orderPerbaikan', 'locations', 'kategoriOrders'));
    }

    public function updateOrderPerbaikan(Request $request, Pengajuan $orderPerbaikan)
    {
        if ((int) $orderPerbaikan->pemohon_id !== (int) auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'kode_inventaris' => 'nullable|string',
            'nama_barang' => 'nullable|string',
            'kategori_id' => 'required|exists:kategori_pengajuan,id',
            'lokasi' => 'nullable|exists:locations,id',
            'keluhan' => 'required|string',
            'prioritas' => 'required|in:low,medium,high',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        $data = [
            'kategori_id' => $validated['kategori_id'],
            'deskripsi' => $validated['keluhan'],
            'prioritas' => $validated['prioritas'],
            'lokasi_id' => $validated['lokasi'] ?? $orderPerbaikan->lokasi_id,
            'kode_inventaris' => $validated['kode_inventaris'] ?? null,
            'nama_barang' => $validated['nama_barang'] ?? null,
        ];

        try {
            $order = $this->service->updateOwn(auth()->user(), $orderPerbaikan, $data, $request->file('foto') ? [$request->file('foto')] : []);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order perbaikan berhasil diperbarui',
                    'data' => $order,
                ]);
            }

            return redirect()
                ->route('user.administrasi-umum.order-perbaikan.show', $order)
                ->with('success', 'Order perbaikan berhasil diperbarui');

        } catch (\Exception $e) {
            $code = $e->getCode() === 403 ? 403 : ($e->getCode() === 422 ? 422 : 500);
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $code);
            }

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function deleteOrderPerbaikan(Pengajuan $orderPerbaikan)
    {
        if ((int) $orderPerbaikan->pemohon_id !== (int) auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $this->service->deleteOwn(auth()->user(), $orderPerbaikan);
            if (request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Order perbaikan berhasil dihapus']);
            }
            return redirect()->route('user.dashboard')
                ->with('success', 'Order has been deleted successfully.');
        } catch (\Exception $e) {
            $code = $e->getCode() === 403 ? 403 : ($e->getCode() === 422 ? 422 : 500);
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], $code);
            }
            return back()->withErrors(['error' => 'Failed to delete order: '.$e->getMessage()]);
        }
    }

    public function confirmOrderPerbaikan(Request $request, Pengajuan $orderPerbaikan)
    {
        $validated = $request->validate([
            'konfirmasi' => 'required|in:selesai,belum',
            'catatan' => 'nullable|string|max:1000',
            'lampiran' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        try {
            $lampiranPath = null;
            if ($request->hasFile('lampiran')) {
                $file = $request->file('lampiran');
                $filename = 'lampiran_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $lampiranPath = $file->storeAs('order-lampiran', $filename, 'public');
            }

            $this->workflow->userConfirm(
                auth()->user(),
                $orderPerbaikan,
                $validated['catatan'] ?? null,
                $validated['konfirmasi'] === 'selesai',
                $lampiranPath ? [$lampiranPath] : []
            );

            return redirect()
                ->route('user.administrasi-umum.order-perbaikan.show', $orderPerbaikan)
                ->with('success', $validated['konfirmasi'] === 'selesai'
                    ? 'Terima kasih, order telah dikonfirmasi selesai.'
                    : 'Order dibuka kembali karena belum selesai.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // stubs for other methods
    public function orderBarang(Request $request) { return view('user.administrasi-umum.order-barang'); }
    public function orderBarangKonfirmasi(Request $request) { return view('user.administrasi-umum.order-barang-konfirmasi'); }
    public function orderBarangTutup(Request $request) { return view('user.administrasi-umum.order-barang-tutup'); }
    public function orderBarangReject(Request $request) { return view('user.administrasi-umum.order-barang-reject'); }
    public function storeOrder(Request $request) { return back(); }
    public function updateOrder(Request $request, Pengajuan $order) { return back(); }
    public function getOrder(Pengajuan $order) { return response()->json($order); }
    public function searchOrders(Request $request) { return response()->json([]); }
}

<?php

namespace App\Http\Controllers\AdministrasiUmum;

use App\Http\Controllers\Controller;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanScopeService;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanWorkflowService;
use App\Services\Pengajuan\PengajuanAttachmentService;
use Illuminate\Http\Request;

class OrderPerbaikanController extends Controller
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanWorkflowService $workflow,
        protected PengajuanScopeService $scope,
    ) {}

    private function ipsrsQuery()
    {
        return $this->scope->visibleTo(auth()->user())->umum();
    }

    private function viewNamespace(?Request $request = null): string
    {
        return 'admin.order-perbaikan';
    }

    private function getStatistics()
    {
        return [
            'totalOrders' => $this->ipsrsQuery()->count(),
            'openOrders' => $this->ipsrsQuery()->where('status', 'open')->count(),
            'inProgressOrders' => $this->ipsrsQuery()->where('status', 'in_progress')->count(),
            'confirmedOrders' => $this->ipsrsQuery()->where('status', 'confirmed')->count(),
            'tutupOrders' => $this->ipsrsQuery()->where('status', 'ditutup')->count(),
            'rejectedOrders' => $this->ipsrsQuery()->where('status', 'rejected')->count(),
            'rendahOrders' => $this->ipsrsQuery()->where('prioritas', 'low')->count(),
            'sedangOrders' => $this->ipsrsQuery()->where('prioritas', 'medium')->count(),
            'tinggiOrders' => $this->ipsrsQuery()->where('prioritas', 'high')->count(),
        ];
    }

    public function index(Request $request)
    {
        $query = $this->ipsrsQuery()->with(['pemohon', 'kategori.jenisList', 'department', 'lokasi', 'penugasan.user']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('pemohon_nama', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('tanggal', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal', '<=', $request->date_to);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status === 'ditutup' ? 'ditutup' : $request->status);
        }
        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();
        $locations = \App\Models\Location::all();

        return view($this->viewNamespace().'.index', array_merge(
            ['orders' => $orders, 'locations' => $locations],
            $this->getStatistics()
        ));
    }

    protected function resolveDepartment($user): ?\App\Models\Department
    {
        if (! empty($user->department_id)) {
            $dept = \App\Models\Department::with(['location', 'building'])->find($user->department_id);
            if ($dept) return $dept;
        }
        if ($user->department) {
            $deptValue = $user->department;
            return \App\Models\Department::with(['location', 'building'])->where('code', $deptValue)->first()
                ?? \App\Models\Department::with(['location', 'building'])->where('name', $deptValue)->first()
                ?? \App\Models\Department::with(['location', 'building'])->whereRaw('LOWER(code) = LOWER(?)', [$deptValue])->first()
                ?? \App\Models\Department::with(['location', 'building'])->whereRaw('LOWER(name) = LOWER(?)', [$deptValue])->first();
        }
        return null;
    }

    public function myorder(Request $request)
    {
        $query = $this->ipsrsQuery()->with(['pemohon', 'kategori.jenisList', 'lokasi', 'penugasan.user'])->where('pemohon_id', auth()->id());

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();
        return view('admin.order-perbaikan.myorder.index', compact('orders'));
    }

    public function showMyorder(Request $request, Pengajuan $orderPerbaikan)
    {
        if ((int) $orderPerbaikan->pemohon_id !== (int) auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'nomor' => $orderPerbaikan->nomor,
                'status' => $orderPerbaikan->status,
                'nama_barang' => $orderPerbaikan->nama_barang,
                'kategori_order' => $orderPerbaikan->kategori?->name,
                'keluhan' => $orderPerbaikan->deskripsi,
                'prioritas' => $orderPerbaikan->prioritas,
                'tanggal' => $orderPerbaikan->tanggal?->format('d/m/Y H:i'),
                'photo_url' => $orderPerbaikan->lampirans->first() ? \Storage::url($orderPerbaikan->lampirans->first()->path) : null,
            ]);
        }

        if ($request->boolean('modal')) {
            $orderPerbaikan->load(['pemohon', 'historis.actor', 'lokasi', 'department']);
            return response(view('admin.order-perbaikan.myorder.component.detail-body', ['order' => $orderPerbaikan])->render());
        }

        $orderPerbaikan->load(['pemohon', 'historis.actor', 'lokasi']);
        return view($this->viewNamespace().'.show', ['orderPerbaikan' => $orderPerbaikan]);
    }

    public function createMyorder()
    {
        $currentDate = now();
        $prefix = 'OP/RTG/MTC-'.$currentDate->format('Ymd');
        $lastOrder = Pengajuan::where('nomor', 'like', $prefix.'%')->orderBy('nomor', 'desc')->first();
        $nomor = $prefix.($lastOrder ? str_pad(((int) substr($lastOrder->nomor, -3)) + 1, 3, '0', STR_PAD_LEFT) : '001');

        $locations = \App\Models\Location::orderBy('name', 'asc')->get();
        $unitProses = \App\Models\UnitProses::where('status', 1)->where('code', '!=', 'SIRS')->get();
        // `jenis` hanyalah accessor BC (bukan relasi Eloquent), sehingga
        // whereHas('jenis') melempar RelationNotFoundException. Pakai scope
        // umum() — sumber kebenaran yang sama dengan halaman web lain.
        $kategoriOrders = KategoriPengajuan::where('status', 1)->umum()->orderBy('name')->get();

        $user = auth()->user();
        $department = $this->resolveDepartment($user);
        $departmentId = $department?->id;
        $departmentLocation = $department?->location;
        $departmentBuilding = $department?->building;
        $unitPengajuCode = $department?->code ?? $user->department ?? 'GENERAL';
        $unitPengajuName = $department?->name ?? \App\Models\Department::where('code', $unitPengajuCode)->value('name') ?? $unitPengajuCode;

        return view('admin.order-perbaikan.myorder.create', compact('nomor', 'locations', 'unitProses', 'user', 'unitPengajuCode', 'unitPengajuName', 'kategoriOrders', 'departmentId', 'departmentLocation', 'departmentBuilding'));
    }

    public function storeMyorder(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'kategori_id' => ['required', 'exists:kategori_pengajuan,id'],
            'department_id' => 'nullable|exists:departments,id',
            'kode_inventaris' => 'nullable|string',
            'nama_barang' => 'nullable|string',
            'lokasi' => 'nullable|exists:locations,id',
            'keluhan' => 'required|string',
            'prioritas' => 'required|in:low,medium,high',
            'tanggal' => 'required|date',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = [
            'kategori_id' => $validated['kategori_id'],
            'department_id' => $validated['department_id'] ?? $user->department_id,
            'kode_inventaris' => $validated['kode_inventaris'] ?? null,
            'nama_barang' => $validated['nama_barang'] ?? null,
            'lokasi_id' => $validated['lokasi'] ?? null,
            'deskripsi' => $validated['keluhan'],
            'prioritas' => $validated['prioritas'],
            'tanggal' => $validated['tanggal'],
        ];

        $files = $request->file('foto') ? [$request->file('foto')] : [];
        $pengajuan = $this->service->create($user, $data, $files);

        return redirect()->route('admin.order-perbaikan.myorder')
            ->with('success', 'Order perbaikan berhasil dibuat dengan nomor: '.$pengajuan->nomor);
    }

    public function editMyorder(Pengajuan $orderPerbaikan)
    {
        if ((int) $orderPerbaikan->pemohon_id !== (int) auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        if ($orderPerbaikan->status !== 'open') {
            return redirect()->route('admin.order-perbaikan.myorder.show', $orderPerbaikan)
                ->with('error', 'Hanya order dengan status open yang dapat diedit.');
        }

        $locations = \App\Models\Location::orderBy('name', 'asc')->get();
        // Sama seperti createMyorder(): pakai scope umum(), bukan whereHas('jenis').
        $kategoriOrders = KategoriPengajuan::where('status', 1)->umum()->orderBy('name')->get();
        $department = $this->resolveDepartment(auth()->user());
        $departmentId = $department?->id;
        $departmentLocation = $department?->location;
        $departmentBuilding = $department?->building;

        return view('admin.order-perbaikan.myorder.edit', compact('orderPerbaikan', 'locations', 'kategoriOrders', 'departmentId', 'departmentLocation', 'departmentBuilding'));
    }

    public function updateMyorder(Request $request, Pengajuan $orderPerbaikan)
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
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
            return redirect()->route('admin.order-perbaikan.myorder.show', $order)
                ->with('success', 'Order perbaikan berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroyMyorder(Pengajuan $orderPerbaikan)
    {
        if ((int) $orderPerbaikan->pemohon_id !== (int) auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $this->service->deleteOwn(auth()->user(), $orderPerbaikan);
            return redirect()->route('admin.order-perbaikan.myorder')
                ->with('success', 'Order perbaikan berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function show(Pengajuan $orderPerbaikan)
    {
        $orderPerbaikan->load(['kategori.jenisList', 'pemohon', 'department', 'lokasi', 'building', 'historis.actor', 'pesans.sender', 'lampirans', 'penugasan.user']);

        // Kandidat PJ bersumber dari role_kategori (sama seperti API/web pengajuan).
        $penugasan = app(\App\Services\Pengajuan\PengajuanPenugasanService::class);
        $canAssign = auth()->user()->can('act', $orderPerbaikan);
        $candidates = $canAssign ? $penugasan->candidates($orderPerbaikan) : collect();
        $activePj = $penugasan->active($orderPerbaikan);

        return view($this->viewNamespace().'.show', [
            'orderPerbaikan' => $orderPerbaikan,
            'candidates' => $candidates,
            'activePj' => $activePj,
            'canAssign' => $canAssign,
        ]);
    }

    public function updatePriority(Request $request, Pengajuan $orderPerbaikan)
    {
        $validated = $request->validate([
            'prioritas' => 'required|in:low,medium,high',
        ]);
        $orderPerbaikan->update(['prioritas' => $validated['prioritas']]);
        return back()->with('success', 'Prioritas order berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Pengajuan $orderPerbaikan)
    {
        $validated = $request->validate([
            // View mengirim 'tutup' untuk "Tutup (Konfirmasi Selesai)" — dulu
            // tidak ada di aturan in:, sehingga submit selalu gagal validasi.
            'status' => 'required|in:in_progress,pending_confirmation,rejected,tutup,closed,ditutup',
            'follow_up' => 'nullable|string',
            'penanggung_jawab_ids' => 'nullable|array',
            'penanggung_jawab_ids.*' => 'integer|exists:users,id',
        ]);

        if (! $this->scope->canAct(auth()->user(), $orderPerbaikan)) {
            abort(403, 'Anda tidak berhak mengubah order ini.');
        }

        if (! empty($validated['follow_up'])) {
            $orderPerbaikan->update(['follow_up' => $validated['follow_up']]);
        }

        // Catatan & lampiran menempel pada entri riwayat status (bukan pesan
        // terpisah) agar tidak muncul dua kali.
        $lampiranFile = $request->file('lampiran');
        $status = $validated['status'];

        if (in_array($status, ['tutup', 'closed', 'ditutup'], true)) {
            if (! in_array($orderPerbaikan->status, ['open', 'in_progress'], true)) {
                // Sudah ditutup/confirmed/rejected — tidak perlu close lagi.
            } else {
                $this->workflow->close(
                    auth()->user(),
                    $orderPerbaikan->fresh(),
                    $validated['follow_up'] ?? 'Pengerjaan selesai, menunggu konfirmasi user.'
                );
            }
        } elseif ($status === 'in_progress' && $orderPerbaikan->status === 'open') {
            $this->workflow->start(auth()->user(), $orderPerbaikan->fresh(), $validated['follow_up'] ?? $validated['notes'] ?? null);
        } else {
            $orderPerbaikan->update(['status' => $status]);
        }

        // Simpan lampiran pada histori terakhir agar muncul di bubble Riwayat Status.
        if (isset($lampiranFile) && $lampiranFile instanceof \Illuminate\Http\UploadedFile) {
            $latestHistori = $orderPerbaikan->fresh()->historis()->latest()->first();
            if ($latestHistori) {
                app(PengajuanAttachmentService::class)->store(
                    $orderPerbaikan->fresh(),
                    [$lampiranFile],
                    'admin_response',
                    auth()->user(),
                    $latestHistori,
                    null
                );
            }
        }
        $newlyAssigned = [];
        if (array_key_exists('penanggung_jawab_ids', $validated)) {
            try {
                $newlyAssigned = $this->service->assignStrict(
                    auth()->user(),
                    $orderPerbaikan->fresh(),
                    $validated['penanggung_jawab_ids'] ?? []
                );
            } catch (\Illuminate\Validation\ValidationException $e) {
                return back()->withErrors($e->errors())->withInput();
            }
        }

        if (! empty($newlyAssigned)) {
            app(\App\Services\Pengajuan\PengajuanNotificationService::class)
                ->notifyAssigned($orderPerbaikan->fresh(), $newlyAssigned, auth()->user());
        }

        return redirect()->back()->with('success', 'Status order berhasil diperbarui.');
    }

    public function start(Request $request, Pengajuan $orderPerbaikan)
    {
        $validated = $request->validate([
            'follow_up' => 'nullable|string',
            'notes' => 'nullable|string',
            'lampiran' => 'nullable|file|max:5120',
        ]);

        $notes = $validated['follow_up'] ?? $validated['notes'] ?? null;

        $this->workflow->start(auth()->user(), $orderPerbaikan, $notes);

        $lampiranFile = $request->file('lampiran');
        if ($lampiranFile instanceof \Illuminate\Http\UploadedFile) {
            $latestHistori = $orderPerbaikan->fresh()->historis()->latest()->first();
            if ($latestHistori) {
                app(PengajuanAttachmentService::class)->store(
                    $orderPerbaikan->fresh(),
                    [$lampiranFile],
                    'admin_response',
                    auth()->user(),
                    $latestHistori,
                    null
                );
            }
        }

        return redirect()->back()->with('success', 'Order berhasil dimulai.');
    }

    public function complete(Request $request, Pengajuan $orderPerbaikan)
    {
        $validated = $request->validate([
            'follow_up' => 'nullable|string',
            'notes' => 'nullable|string',
            'lampiran' => 'nullable|file|max:5120',
        ]);

        $notes = $validated['follow_up'] ?? $validated['notes'] ?? 'Pengerjaan selesai, menunggu konfirmasi user.';

        $this->workflow->close(auth()->user(), $orderPerbaikan, $notes);

        $lampiranFile = $request->file('lampiran');
        if ($lampiranFile instanceof \Illuminate\Http\UploadedFile) {
            $latestHistori = $orderPerbaikan->fresh()->historis()->latest()->first();
            if ($latestHistori) {
                app(PengajuanAttachmentService::class)->store(
                    $orderPerbaikan->fresh(),
                    [$lampiranFile],
                    'admin_response',
                    auth()->user(),
                    $latestHistori,
                    null
                );
            }
        }

        return redirect()->route('admin.order-perbaikan.show', $orderPerbaikan)
            ->with('success', 'Order ditutup dan menunggu konfirmasi selesai dari user.');
    }

    public function confirm(Pengajuan $orderPerbaikan)
    {
        $this->workflow->adminConfirm(auth()->user(), $orderPerbaikan);
        return redirect()->route('admin.order-perbaikan.show', $orderPerbaikan)->with('success', 'Order berhasil dikonfirmasi.');
    }

    public function reject(Pengajuan $orderPerbaikan)
    {
        $this->workflow->adminReject(auth()->user(), $orderPerbaikan);
        return redirect()->route('admin.order-perbaikan.show', $orderPerbaikan)->with('success', 'Order berhasil ditolak.');
    }

    public function inProgress(Request $request)
    {
        $orders = $this->ipsrsQuery()->with(['pemohon', 'kategori.jenisList', 'lokasi', 'penugasan.user'])->where('status', 'in_progress')->latest()->paginate(10)->withQueryString();
        return view($this->viewNamespace().'.in-progress', ['orders' => $orders, 'inProgressOrders' => $this->ipsrsQuery()->where('status', 'in_progress')->count(), 'locations' => \App\Models\Location::all()]);
    }

    public function confirmed(Request $request)
    {
        $orders = $this->ipsrsQuery()->with(['pemohon', 'kategori.jenisList', 'penugasan.user'])->where('status', 'confirmed')->latest()->paginate(10)->withQueryString();
        return view($this->viewNamespace().'.confirmed', array_merge(['orders' => $orders], $this->getStatistics()));
    }

    public function rejected(Request $request)
    {
        $orders = $this->ipsrsQuery()->with(['pemohon', 'kategori.jenisList', 'penugasan.user'])->where('status', 'rejected')->latest()->paginate(10)->withQueryString();
        return view($this->viewNamespace().'.rejected', array_merge(['orders' => $orders], $this->getStatistics()));
    }

    public function filterOrders($type, $value)
    {
        $query = $this->ipsrsQuery()->latest();
        if ($type === 'status') $query->where('status', $value);
        if ($type === 'prioritas') $query->where('prioritas', strtolower($value));
        $orders = $query->paginate(10);
        return view($this->viewNamespace().'.index', array_merge(['orders' => $orders], $this->getStatistics()));
    }

    public function total(Request $request)
    {
        $orders = $this->ipsrsQuery()->with(['pemohon', 'kategori.jenisList', 'lokasi', 'penugasan.user'])->latest()->paginate(10)->withQueryString();
        return view($this->viewNamespace().'.total', array_merge(['orders' => $orders], $this->getStatistics()));
    }

    public function export(Request $request)
    {
        return back()->with('info', 'Export not yet migrated.');
    }

    // stubs
    public function orderBarang(Request $request) { return view('user.administrasi-umum.order-barang'); }
    public function orderBarangKonfirmasi(Request $request) { return view('user.administrasi-umum.order-barang-konfirmasi'); }
    public function orderBarangTutup(Request $request) { return view('user.administrasi-umum.order-barang-tutup'); }
    public function orderBarangReject(Request $request) { return view('user.administrasi-umum.order-barang-reject'); }
    public function storeOrder(Request $request) { return back(); }
    public function updateOrder(Request $request, Pengajuan $order) { return back(); }
    public function getOrder(Pengajuan $order) { return response()->json($order); }
    public function searchOrders(Request $request) { return response()->json([]); }
    public function rendah() { return $this->filterOrders('prioritas', 'low'); }
    public function sedang() { return $this->filterOrders('prioritas', 'medium'); }
    public function tinggi() { return $this->filterOrders('prioritas', 'high'); }
}

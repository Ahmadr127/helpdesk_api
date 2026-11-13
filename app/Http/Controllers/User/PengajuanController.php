<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Services\Pengajuan\LegacyPengajuanResolver;
use App\Services\Pengajuan\PengajuanScopeService;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanWorkflowService;
use App\Services\Report\ReportService;
use Illuminate\Http\Request;

class PengajuanController extends Controller
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanWorkflowService $workflow,
        protected PengajuanScopeService $scope,
        protected LegacyPengajuanResolver $resolver,
        protected ReportService $reportService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        // Default filter: bulan berjalan (berlaku saat pertama dibuka; submit form = kontrol penuh).
        if (! $request->filled('_filter')) {
            $request->merge([
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->endOfMonth()->toDateString(),
            ]);
        }

        // Query dasar tetap dibatasi scope yang boleh dilihat user (fail-closed).
        $base = $this->reportService->applyFilters($this->scope->visibleTo($user), $request);

        $summary = $this->reportService->summary($base);
        $pengajuans = $this->reportService->paginate(clone $base, $request);

        // Partial resources/views/reports/_report.blade.php dipakai bersama shell admin.
        return view('user.pengajuan.index', array_merge([
            'pengajuans' => $pengajuans,
            'summary' => $summary,
            'route' => route('user.pengajuan.index'),
            'detailRoute' => 'user.pengajuan.show',
            'exportUrl' => null,
            'showJenis' => true,
            'showDepartment' => false,
            'showYear' => false,
            'selectable' => false,
            'columns' => ['nomor', 'tanggal', 'jenis', 'kategori', 'prioritas', 'status', 'pj', 'closed_at', 'aksi'],
        ], $this->reportService->options()));
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        // Form Tiket hanya boleh menampilkan kategori flavour SIRS — padanan
        // scope sirs() yang sudah dipakai TicketController.
        $kategoris = KategoriPengajuan::where('status', 1)->sirs()->orderBy('name')->get();
        $locations = \App\Models\Location::where('status', 1)->get();
        $buildings = \App\Models\Building::where('status', 1)->get();

        $userDepartment = null;
        if (! empty($user->department_id)) {
            $userDepartment = \App\Models\Department::with(['location', 'building'])->find($user->department_id);
        }
        if (! $userDepartment && $user->department) {
            $deptValue = $user->department;
            $userDepartment = \App\Models\Department::with(['location', 'building'])->where('code', $deptValue)->first()
                ?? \App\Models\Department::with(['location', 'building'])->where('name', $deptValue)->first()
                ?? \App\Models\Department::with(['location', 'building'])->whereRaw('LOWER(code) = LOWER(?)', [$deptValue])->first()
                ?? \App\Models\Department::with(['location', 'building'])->whereRaw('LOWER(name) = LOWER(?)', [$deptValue])->first();
        }

        return view('user.pengajuan.create', compact('kategoris', 'locations', 'buildings', 'userDepartment'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kategori_id' => ['required', 'exists:kategori_pengajuan,id'],
            'deskripsi' => ['required', 'string'],
            'prioritas' => ['nullable', 'in:low,medium,high'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'lokasi_id' => ['nullable', 'exists:locations,id'],
            'building_id' => ['nullable', 'exists:buildings,id'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $files = $request->file('photos', []);
        $pengajuan = $this->service->create($request->user(), $data, is_array($files) ? $files : [$files]);

        return redirect()->route('user.pengajuan.show', $pengajuan->id)
            ->with('success', 'Pengajuan berhasil dibuat.');
    }

    public function show(Pengajuan $pengajuan)
    {
        $this->authorize('view', $pengajuan);
        $pengajuan->load(['kategori.jenisList', 'pemohon', 'department', 'lokasi', 'building', 'historis.actor', 'pesans.sender', 'lampirans', 'penugasan.user']);

        // Hanya pemegang scope role_kategori / PJ aktif yang boleh menugaskan.
        // Pemohon biasa hanya bisa melihat PJ, bukan mengubahnya.
        $canAssign = auth()->user()->can('act', $pengajuan);
        $penugasan = app(\App\Services\Pengajuan\PengajuanPenugasanService::class);
        $candidates = $canAssign ? $penugasan->candidates($pengajuan) : collect();
        $activePj = $penugasan->active($pengajuan);

        return view('user.pengajuan.show', compact('pengajuan', 'candidates', 'activePj', 'canAssign'));
    }

    public function edit(Pengajuan $pengajuan)
    {
        $this->authorize('update', $pengajuan);
        $user = auth()->user();
        // Sama seperti create(): hanya kategori flavour SIRS.
        $kategoris = KategoriPengajuan::where('status', 1)->sirs()->orderBy('name')->get();
        $locations = \App\Models\Location::where('status', 1)->get();
        $buildings = \App\Models\Building::where('status', 1)->get();

        $userDepartment = null;
        if (! empty($pengajuan->department_id)) {
            $userDepartment = \App\Models\Department::with(['location', 'building'])->find($pengajuan->department_id);
        }
        if (! $userDepartment && ! empty($user->department_id)) {
            $userDepartment = \App\Models\Department::with(['location', 'building'])->find($user->department_id);
        }

        return view('user.pengajuan.edit', compact('pengajuan', 'kategoris', 'locations', 'buildings', 'userDepartment'));
    }

    public function update(Request $request, Pengajuan $pengajuan)
    {
        $this->authorize('update', $pengajuan);
        $data = $request->validate([
            'kategori_id' => ['nullable', 'exists:kategori_pengajuan,id'],
            'deskripsi' => ['nullable', 'string'],
            'prioritas' => ['nullable', 'in:low,medium,high'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'lokasi_id' => ['nullable', 'exists:locations,id'],
            'building_id' => ['nullable', 'exists:buildings,id'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['nullable', 'image', 'max:5120'],
        ]);

        $files = $request->file('photos', []);
        $this->service->updateOwn($request->user(), $pengajuan, $data, is_array($files) ? $files : []);

        return redirect()->route('user.pengajuan.show', $pengajuan->id)
            ->with('success', 'Pengajuan berhasil diperbarui.');
    }

    public function destroy(Pengajuan $pengajuan)
    {
        $this->authorize('delete', $pengajuan);
        $this->service->deleteOwn(auth()->user(), $pengajuan);

        return redirect()->route('user.pengajuan.index')
            ->with('success', 'Pengajuan berhasil dihapus.');
    }

    public function start(Pengajuan $pengajuan)
    {
        $this->workflow->start(auth()->user(), $pengajuan);

        return back()->with('success', 'Pengajuan dimulai.');
    }

    public function close(Request $request, Pengajuan $pengajuan)
    {
        $this->workflow->close(auth()->user(), $pengajuan, $request->input('notes'));

        return back()->with('success', 'Pengajuan ditutup.');
    }

    public function message(Request $request, Pengajuan $pengajuan)
    {
        $validated = $request->validate([
            'message' => ['required_without:body', 'string', 'nullable'],
            'body' => ['required_without:message', 'string', 'nullable'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:5120'],
        ]);

        $body = $validated['message'] ?? $validated['body'];

        $files = [];
        if ($request->hasFile('photo')) {
            $files[] = $request->file('photo');
        } elseif ($request->hasFile('photos')) {
            $files = $request->file('photos');
        }

        $this->service->addMessage(auth()->user(), $pengajuan, $body, $files);

        return back()->with('success', 'Pesan terkirim.');
    }

    public function assign(Request $request, Pengajuan $pengajuan)
    {
        // Bukan 'update' (owner-only): PJ/admin berhak menugaskan sesuai role_kategori.
        $this->authorize('act', $pengajuan);

        $userIds = $request->input('user_ids', []);
        if (! is_array($userIds)) {
            $userIds = [$userIds];
        }

        try {
            $newlyAssigned = $this->service->assignStrict(auth()->user(), $pengajuan, $userIds);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        if (! empty($newlyAssigned)) {
            app(\App\Services\Pengajuan\PengajuanNotificationService::class)
                ->notifyAssigned($pengajuan->fresh(), $newlyAssigned, auth()->user());
        }

        return back()->with('success', 'Penanggung jawab berhasil diperbarui.');
    }

    public function confirm(Request $request, Pengajuan $pengajuan)
    {
        $this->authorize('view', $pengajuan);

        $request->validate([
            'action' => 'required|in:confirm,reject',
        ]);

        $selesai = $request->input('action') === 'confirm';

        try {
            $this->workflow->userConfirm(auth()->user(), $pengajuan, null, $selesai, []);

            return back()->with('success', $selesai
                ? 'Terima kasih, pengajuan telah dikonfirmasi selesai.'
                : 'Pengajuan dibuka kembali karena belum selesai.');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Unauthorized')) {
                abort(403);
            }

            return back()->with('error', $e->getMessage())->withInput();
        }
    }

}
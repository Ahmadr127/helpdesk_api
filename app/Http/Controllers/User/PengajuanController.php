<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Services\Pengajuan\LegacyPengajuanResolver;
use App\Services\Pengajuan\PengajuanScopeService;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanWorkflowService;
use Illuminate\Http\Request;

class PengajuanController extends Controller
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanWorkflowService $workflow,
        protected PengajuanScopeService $scope,
        protected LegacyPengajuanResolver $resolver,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $query = $this->scope->visibleTo($user);

        if ($request->filled('jenis')) {
            $query->whereHas('kategori.jenis', fn ($q) => $q->where('code', $request->jenis));
        }

        $pengajuans = $query->with(['kategori.jenis', 'pemohon'])->latest()->paginate(20);
        $kategoris = KategoriPengajuan::where('status', 1)->get();

        return view('user.pengajuan.index', compact('pengajuans', 'kategoris'));
    }

    public function create(Request $request)
    {
        $jenis = $request->get('jenis', 'IT');
        $kategoris = KategoriPengajuan::where('status', 1)
            ->whereHas('jenis', fn ($q) => $q->where('code', $jenis))
            ->get();

        return view('user.pengajuan.create', compact('jenis', 'kategoris'));
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
            'unit_penerima' => ['nullable', 'string'],
            'kode_inventaris' => ['nullable', 'string'],
            'nama_barang' => ['nullable', 'string'],
            'unit_proses_id' => ['nullable', 'exists:unit_proses,id'],
        ]);

        $files = $request->file('photos', []);
        $pengajuan = $this->service->create($request->user(), $data, is_array($files) ? $files : [$files]);

        return redirect()->route('user.pengajuan.show', $pengajuan->id)
            ->with('success', 'Pengajuan berhasil dibuat.');
    }

    public function show(Pengajuan $pengajuan)
    {
        $this->authorize('view', $pengajuan);
        $pengajuan->load(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'orderDetail', 'historis.actor', 'pesans.sender', 'lampirans', 'penugasan.user']);

        return view('user.pengajuan.show', compact('pengajuan'));
    }

    public function edit(Pengajuan $pengajuan)
    {
        $this->authorize('update', $pengajuan);
        $kategoris = KategoriPengajuan::where('status', 1)->get();

        return view('user.pengajuan.edit', compact('pengajuan', 'kategoris'));
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
        $request->validate(['body' => ['required', 'string']]);
        $this->service->addMessage(auth()->user(), $pengajuan, $request->input('body'), $request->file('photos', []));

        return back()->with('success', 'Pesan terkirim.');
    }
}
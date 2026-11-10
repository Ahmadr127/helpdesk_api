<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisPengajuan;
use App\Models\KategoriPengajuan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriPengajuanController extends Controller
{
    public function index(Request $request)
    {
        $query = KategoriPengajuan::with('jenisList');

        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(code) LIKE ?', ["%{$search}%"]);
            });
        }
        // Filter multi-jenis: kategori yang terhubung ke jenis ini ditampilkan.
        if ($request->filled('jenis_id')) {
            $query->whereHas('jenisList', fn ($q) => $q->where('jenis_pengajuan.id', $request->jenis_id));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $kategoris = $query->orderBy('created_at', 'desc')->paginate(10);
        $jenisList = JenisPengajuan::where('is_active', true)->orderBy('code')->get();
        $searchOptions = KategoriPengajuan::orderBy('name')->pluck('name', 'name')->toArray();

        return view('admin.master.kategori-pengajuan', compact('kategoris', 'jenisList', 'searchOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis_ids' => 'required|array|min:1',
            'jenis_ids.*' => 'exists:jenis_pengajuan,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'status' => 'required|boolean',
        ]);

        try {
            $kategori = KategoriPengajuan::create([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
            ]);

            $kategori->syncJenis($validated['jenis_ids']);
        } catch (\Throwable $e) {
            return redirect()->route('admin.master.kategori-pengajuan.index')
                ->with('error', 'Gagal menambahkan kategori: '.$e->getMessage());
        }

        return redirect()->route('admin.master.kategori-pengajuan.index')
            ->with('success', 'Kategori Pengajuan berhasil ditambahkan.');
    }

    public function update(Request $request, KategoriPengajuan $kategoriPengajuan)
    {
        $validated = $request->validate([
            'jenis_ids' => 'required|array|min:1',
            'jenis_ids.*' => 'exists:jenis_pengajuan,id',
            'name' => ['required', 'string', 'max:255', Rule::unique('kategori_pengajuan')->ignore($kategoriPengajuan)],
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'status' => 'required|boolean',
        ]);

        try {
            $kategoriPengajuan->update([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
            ]);

            $kategoriPengajuan->syncJenis($validated['jenis_ids']);
        } catch (\Throwable $e) {
            return redirect()->route('admin.master.kategori-pengajuan.index')
                ->with('error', 'Gagal memperbarui kategori: '.$e->getMessage());
        }

        return redirect()->route('admin.master.kategori-pengajuan.index')
            ->with('success', 'Kategori Pengajuan berhasil diperbarui.');
    }

    public function destroy(KategoriPengajuan $kategoriPengajuan)
    {
        try {
            $kategoriPengajuan->delete();
            return redirect()->route('admin.master.kategori-pengajuan.index')
                ->with('success', 'Kategori Pengajuan berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('admin.master.kategori-pengajuan.index')
                ->with('error', 'Gagal menghapus Kategori Pengajuan. Data mungkin sedang digunakan.');
        }
    }
}
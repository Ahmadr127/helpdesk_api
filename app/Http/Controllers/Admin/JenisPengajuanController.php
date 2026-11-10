<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisPengajuan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JenisPengajuanController extends Controller
{
    public function index(Request $request)
    {
        $query = JenisPengajuan::query();

        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(code) LIKE ?', ["%{$search}%"]);
            });
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $jenisList = $query->orderBy('id')->paginate(10)->withQueryString();
        $searchOptions = JenisPengajuan::orderBy('name')->pluck('name', 'name')->toArray();

        return view('admin.master.jenis-pengajuan', compact('jenisList', 'searchOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:jenis_pengajuan,code',
            'name' => 'required|string|max:255',
            'prefix' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
        ]);

        JenisPengajuan::create($validated);

        return redirect()->route('admin.master.jenis-pengajuan.index')
            ->with('success', 'Jenis Pengajuan berhasil ditambahkan.');
    }

    public function update(Request $request, JenisPengajuan $jenisPengajuan)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('jenis_pengajuan')->ignore($jenisPengajuan)],
            'name' => ['required', 'string', 'max:255'],
            'prefix' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
        ]);

        $jenisPengajuan->update($validated);

        return redirect()->route('admin.master.jenis-pengajuan.index')
            ->with('success', 'Jenis Pengajuan berhasil diperbarui.');
    }

    public function destroy(JenisPengajuan $jenisPengajuan)
    {
        try {
            // Cek masih dipakai pivot/sequence agar tidak kehilangan referensi
            if ($jenisPengajuan->kategoris()->exists() || $jenisPengajuan->documentSequences()->exists()) {
                return redirect()->route('admin.master.jenis-pengajuan.index')
                    ->with('error', 'Jenis masih terhubung ke kategori atau sequence, tidak bisa dihapus.');
            }

            $jenisPengajuan->delete();

            return redirect()->route('admin.master.jenis-pengajuan.index')
                ->with('success', 'Jenis Pengajuan berhasil dihapus.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.master.jenis-pengajuan.index')
                ->with('error', 'Gagal menghapus jenis. Data mungkin sedang digunakan.');
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitProses;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitProsesController extends Controller
{
    public function index(Request $request)
    {
        $query = UnitProses::with('categories');

        // Search (case-insensitive) by name or code, LOWER() matches lowercase
        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(code) LIKE ?', ["%{$search}%"]);
            });
        }

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $unitProses = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('admin.master.unit-proses', compact('unitProses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:unit_proses',
            'code' => 'required|string|max:50|unique:unit_proses',
            'status' => 'required|boolean',
        ]);

        UnitProses::create($request->all());

        return redirect()->route('admin.master.unit-proses.index')
            ->with('success', 'Unit Proses berhasil ditambahkan.');
    }

    public function update(Request $request, UnitProses $unitProse)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('unit_proses')->ignore($unitProse)],
            'code' => ['required', 'string', 'max:50', Rule::unique('unit_proses')->ignore($unitProse)],
            'status' => 'required|boolean',
        ]);

        $unitProse->update($request->all());

        return redirect()->route('admin.master.unit-proses.index')
            ->with('success', 'Unit Proses berhasil diperbarui.');
    }

    public function destroy(UnitProses $unitProse)
    {
        try {
            $unitProse->delete();

            return redirect()->route('admin.master.unit-proses.index')
                ->with('success', 'Unit Proses berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('admin.master.unit-proses.index')
                ->with('error', 'Gagal menghapus Unit Proses. Data mungkin sedang digunakan.');
        }
    }
}

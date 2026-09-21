<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = KategoriOrder::query();

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

        $kategoriOrders = $query->orderBy('created_at', 'desc')->paginate(10);
        $searchOptions = KategoriOrder::orderBy('name')->pluck('name', 'name')->toArray();

        return view('admin.master.kategori-order', compact('kategoriOrders', 'searchOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:kategori_order',
            'code' => 'nullable|string|max:50|unique:kategori_order',
            'status' => 'required|boolean',
        ]);

        KategoriOrder::create($request->all());

        return redirect()->route('admin.master.kategori-order.index')
            ->with('success', 'Kategori Order berhasil ditambahkan.');
    }

    public function update(Request $request, KategoriOrder $kategoriOrder)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('kategori_order')->ignore($kategoriOrder)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('kategori_order')->ignore($kategoriOrder)],
            'status' => 'required|boolean',
        ]);

        $kategoriOrder->update($request->all());

        return redirect()->route('admin.master.kategori-order.index')
            ->with('success', 'Kategori Order berhasil diperbarui.');
    }

    public function destroy(KategoriOrder $kategoriOrder)
    {
        try {
            $kategoriOrder->delete();

            return redirect()->route('admin.master.kategori-order.index')
                ->with('success', 'Kategori Order berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('admin.master.kategori-order.index')
                ->with('error', 'Gagal menghapus Kategori Order. Data mungkin sedang digunakan.');
        }
    }
}

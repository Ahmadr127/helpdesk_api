<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\KategoriOrder;
use App\Models\User;
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

        $kategoriOrders = $query->with(['users'])->orderBy('created_at', 'desc')->paginate(10);
        $searchOptions = KategoriOrder::orderBy('name')->pluck('name', 'name')->toArray();
        $users = User::where('status', 1)->orderBy('name')->get();

        return view('admin.master.kategori-order', compact('kategoriOrders', 'searchOptions', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:kategori_order',
            'code' => 'nullable|string|max:50|unique:kategori_order',
            'status' => 'required|boolean',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $kategori = KategoriOrder::create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'status' => $validated['status'],
        ]);

        $kategori->users()->sync($validated['user_ids'] ?? []);

        return redirect()->route('admin.master.kategori-order.index')
            ->with('success', 'Kategori Order berhasil ditambahkan.');
    }

    public function update(Request $request, KategoriOrder $kategoriOrder)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('kategori_order')->ignore($kategoriOrder)],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('kategori_order')->ignore($kategoriOrder)],
            'status' => 'required|boolean',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $kategoriOrder->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'status' => $validated['status'],
        ]);

        $kategoriOrder->users()->sync($validated['user_ids'] ?? []);

        return redirect()->route('admin.master.kategori-order.index')
            ->with('success', 'Kategori Order berhasil diperbarui.');
    }

    public function assignAllCategories(Request $request)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    $user = User::findOrFail($validated['user_id']);
    $activeIds = KategoriOrder::where('status', 1)->pluck('id')->all();

    foreach ($activeIds as $id) {
        $kategori = KategoriOrder::find($id);
        $kategori->users()->syncWithoutDetaching([$user->id]);
    }

    return redirect()->route('admin.master.kategori-order.index')
        ->with('success', 'User '.$user->name.' ditugaskan ke semua kategori aktif.');
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BuildingController extends Controller
{
    public function index(Request $request)
    {
        $query = Building::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $buildings = $query->latest()->paginate(10)->withQueryString();
        return view('admin.master.buildings', compact('buildings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:buildings',
            'status' => 'required|boolean'
        ]);

        Building::create($request->all());

        return redirect()->route('admin.master.buildings.index')
            ->with('success', 'Gedung berhasil ditambahkan.');
    }

    public function update(Request $request, Building $building)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('buildings')->ignore($building)],
            'status' => 'required|boolean'
        ]);

        $building->update($request->all());

        return redirect()->route('admin.master.buildings.index')
            ->with('success', 'Gedung berhasil diperbarui.');
    }

    public function destroy(Building $building)
    {
        try {
            $building->delete();
            return redirect()->route('admin.master.buildings.index')
                ->with('success', 'Gedung berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('admin.master.buildings.index')
                ->with('error', 'Gagal menghapus Gedung. Data mungkin sedang digunakan.');
        }
    }
} 
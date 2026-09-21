<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\UnitProses;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::with('unitProses');

        // Search (case-insensitive) by category name or unit proses name, LOWER() matches lowercase
        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereHas('unitProses', function ($uq) use ($search) {
                      $uq->whereRaw('LOWER(unit_proses.name) LIKE ?', ["%{$search}%"]);
                  });
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
        if ($request->filled('unit_proses_id')) {
            $query->where('unit_proses_id', $request->unit_proses_id);
        }

        $categories = $query->orderBy('created_at', 'desc')->paginate(10);
        $unitProses = UnitProses::where('status', true)->get();
        // Search mencakup nama kategori + nama unit proses
        $searchOptions = Category::orderBy('name')->pluck('name', 'name')
            ->merge($unitProses->sortBy('name')->pluck('name', 'name'))
            ->toArray();

        return view('admin.master.categories', compact('categories', 'unitProses', 'searchOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|boolean',
            'unit_proses_id' => 'required|exists:unit_proses,id',
        ]);

        Category::create($validated);

        return redirect()->route('admin.master.categories.index')
            ->with('success', 'Category created successfully');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|boolean',
            'unit_proses_id' => 'required|exists:unit_proses,id',
        ]);

        $category->update($validated);

        return redirect()->route('admin.master.categories.index')
            ->with('success', 'Category updated successfully');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.master.categories.index')
            ->with('success', 'Category deleted successfully');
    }
}

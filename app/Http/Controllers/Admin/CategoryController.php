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

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->has('unit_proses_id')) {
            $query->where('unit_proses_id', $request->unit_proses_id);
        }

        $categories = $query->orderBy('created_at', 'desc')->paginate(10);
        $unitProses = UnitProses::where('status', true)->get();
        
        return view('admin.master.categories', compact('categories', 'unitProses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|boolean',
            'unit_proses_id' => 'required|exists:unit_proses,id'
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
            'unit_proses_id' => 'required|exists:unit_proses,id'
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
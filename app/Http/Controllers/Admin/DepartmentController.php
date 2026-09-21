<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::with(['location', 'building']);

        // Search (case-insensitive) by name or code, LOWER() matches lowercase
        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(code) LIKE ?', ["%{$search}%"]);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $departments = $query->latest()->paginate(10)->withQueryString();
        $locations = Location::where('status', 1)->orderBy('name')->get();
        $buildings = Building::where('status', 1)->orderBy('name')->get();
        $searchOptions = Department::orderBy('name')->pluck('name', 'name')->toArray();

        return view('admin.master.departments', compact('departments', 'locations', 'buildings', 'searchOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments',
            'location_id' => 'nullable|exists:locations,id',
            'building_id' => 'nullable|exists:buildings,id',
            'status' => 'required|boolean',
        ]);

        Department::create($validated);

        return redirect()->route('admin.master.departments.index')->with('success', 'Department created successfully');
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code,'.$department->id,
            'location_id' => 'nullable|exists:locations,id',
            'building_id' => 'nullable|exists:buildings,id',
            'status' => 'required|boolean',
        ]);

        $department->update($validated);

        return redirect()->route('admin.master.departments.index')->with('success', 'Department updated successfully');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('admin.master.departments.index')->with('success', 'Department deleted successfully');
    }
}

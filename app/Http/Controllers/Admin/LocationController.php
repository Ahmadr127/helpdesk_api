<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Building;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $query = Location::with('building');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by building
        if ($request->has('building_id')) {
            $query->where('building_id', $request->building_id);
        }

        // Filter by date range
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $locations = $query->latest()->paginate(10)->withQueryString();
        $buildings = Building::where('status', 1)->get();
        return view('admin.master.locations', compact('locations', 'buildings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'building_id' => 'required|exists:buildings,id',
            'status' => 'required|boolean',
        ]);

        Location::create($validated);
        return redirect()->route('admin.master.locations.index')->with('success', 'Location created successfully');
    }

    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'building_id' => 'required|exists:buildings,id',
            'status' => 'required|boolean',
        ]);

        $location->update($validated);
        return redirect()->route('admin.master.locations.index')->with('success', 'Location updated successfully');
    }

    public function destroy(Location $location)
    {
        $location->delete();
        return redirect()->route('admin.master.locations.index')->with('success', 'Location deleted successfully');
    }

    // API endpoint untuk mendapatkan locations berdasarkan building
    public function getLocationsByBuilding(Building $building)
    {
        $locations = Location::where('building_id', $building->id)
            ->where('status', 1)
            ->get(['id', 'name']);
        
        return response()->json($locations);
    }
} 
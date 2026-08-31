<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::orderBy('name')->get();
        return view('admin.master.positions', compact('positions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:positions,code'
        ]);

        Position::create([
            'name' => $request->name,
            'code' => $request->code,
            'status' => true
        ]);

        return back()->with('success', 'Position created successfully.');
    }

    public function update(Request $request, Position $position)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:positions,code,' . $position->id
        ]);

        $position->update([
            'name' => $request->name,
            'code' => $request->code
        ]);

        return back()->with('success', 'Position updated successfully.');
    }

    public function toggleStatus(Position $position)
    {
        $position->update([
            'status' => !$position->status
        ]);

        return back()->with('success', 'Position status updated successfully.');
    }

    public function destroy(Position $position)
    {
        // Check if position is being used by any user
        if ($position->users()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete position that is being used by users.']);
        }

        $position->delete();
        return back()->with('success', 'Position deleted successfully.');
    }
} 
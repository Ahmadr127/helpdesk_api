<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Department;
use App\Models\Building;
use App\Models\Location;
use App\Models\UnitProses;
use App\Models\Position;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    public function index()
    {
        // Tampilkan 5 data terbaru untuk setiap model
        $limit = 5;

        $categories = Category::with('unitProses')->latest()->limit($limit)->get();
        $departments = Department::latest()->limit($limit)->get();
        $buildings = Building::latest()->limit($limit)->get();
        $locations = Location::with('building')->latest()->limit($limit)->get();
        $unitProses = UnitProses::latest()->limit($limit)->get();
        $positions = Position::latest()->limit($limit)->get();

        return view('admin.master.index', compact(
            'categories',
            'departments',
            'buildings',
            'locations',
            'unitProses',
            'positions'
        ));
    }

    public function getData(Request $request, $type)
    {
        $limit = $request->input('limit', 5);
        $status = $request->input('status');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // Query untuk setiap model
        $query = match($type) {
            'categories' => Category::query(),
            'departments' => Department::query(),
            'buildings' => Building::query(),
            'locations' => Location::with('building'),
            'positions' => Position::query(),
        };

        // Terapkan filter
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }
        
        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        // Gunakan limit() sebagai pengganti take() agar lebih fleksibel
        $data = $query->latest()->limit($limit)->get();
        
        // Render partial view
        $html = view("admin.master.partials.{$type}-table", [
            $type => $data
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'count' => $data->count() // Tambahkan informasi jumlah data
        ]);
    }

    public function bulkAction(Request $request, $type)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'selected' => 'required|array',
            'selected.*' => 'required|integer'
        ]);

        $action = $request->input('action');
        $selected = $request->input('selected');
        $count = count($selected);
        $status = false;
        $message = '';

        switch ($type) {
            case 'unit-proses':
                $model = UnitProses::whereIn('id', $selected);
                $typeMessage = "Unit Proses";
                break;
            case 'departments':
                $model = Department::whereIn('id', $selected);
                $typeMessage = "Departemen";
                break;
            case 'buildings':
                $model = Building::whereIn('id', $selected);
                $typeMessage = "Gedung";
                break;
            case 'locations':
                $model = Location::whereIn('id', $selected);
                $typeMessage = "Lokasi";
                break;
            case 'categories':
                $model = Category::whereIn('id', $selected);
                $typeMessage = "Kategori";
                break;
            default:
                return redirect()->back()->with('error', 'Tipe data tidak valid');
        }
        
        try {
            switch ($action) {
                case 'activate':
                    $model->update(['status' => true]);
                    $status = true;
                    $message = "$count $typeMessage berhasil diaktifkan.";
                    break;
                case 'deactivate':
                    $model->update(['status' => false]);
                    $status = true;
                    $message = "$count $typeMessage berhasil dinonaktifkan.";
                    break;
                case 'delete':
                    $model->delete();
                    $status = true;
                    $message = "$count $typeMessage berhasil dihapus.";
                    break;
            }
            return redirect()->back()->with($status ? 'success' : 'error', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal melakukan tindakan. Beberapa data mungkin sedang digunakan.');
        }
    }

    public function updateLimit(Request $request, $type)
    {
        $validated = $request->validate([
            'limit' => 'required|integer|min:5|max:20'
        ]);

        session(["${type}_limit" => $validated['limit']]);
        
        return response()->json(['success' => true]);
    }

    public function saveSettings(Request $request)
    {
        $validated = $request->validate([
            'limit' => 'required|integer|min:5|max:20',
            'status' => 'nullable|in:0,1',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        session([
            'global_limit' => $validated['limit'],
            'global_status' => $validated['status'],
            'global_from_date' => $validated['from_date'],
            'global_to_date' => $validated['to_date'],
        ]);

        return response()->json(['success' => true]);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
use App\Models\KategoriOrder;
use App\Models\Location;
use App\Models\Position;
use App\Models\UnitProses;
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
        $locations = Location::query()->latest()->limit($limit)->get();
        $unitProses = UnitProses::latest()->limit($limit)->get();
        $positions = Position::latest()->limit($limit)->get();
        $kategoriOrders = KategoriOrder::latest()->limit($limit)->get();

        return view('admin.master.index', compact(
            'categories',
            'departments',
            'buildings',
            'locations',
            'unitProses',
            'positions',
            'kategoriOrders'
        ));
    }

    /**
     * Canonical type map: menerima varian dash/underscore dari route.
     * key = tipe ternormalisasi, value = [model, relasi eager, kolom search].
     */
    protected function masterMap(): array
    {
        return [
            'categories' => [Category::class, ['unitProses'], ['name']],
            'departments' => [Department::class, ['location', 'building'], ['name', 'code']],
            'buildings' => [Building::class, [], ['name', 'code']],
            'locations' => [Location::class, [], ['name']],
            'unit_proses' => [UnitProses::class, ['categories'], ['name', 'code']],
            'positions' => [Position::class, [], ['name', 'code']],
            'kategori_order' => [KategoriOrder::class, [], ['name', 'code']],
        ];
    }

    protected function normalizeType(string $type): ?string
    {
        $key = str_replace('-', '_', strtolower($type));

        return array_key_exists($key, $this->masterMap()) ? $key : null;
    }

    public function getData(Request $request, $type)
    {
        $normalized = $this->normalizeType($type);
        if (! $normalized) {
            return response()->json([
                'success' => false,
                'message' => 'Tipe data tidak valid.',
            ], 422);
        }

        [$model, $with, $searchColumns] = $this->masterMap()[$normalized];

        $limit = (int) $request->input('limit', 5);
        $limit = max(1, min(50, $limit));
        $status = $request->input('status');
        $search = trim((string) $request->input('search', ''));
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $query = $model::query()->with($with);

        // Terapkan filter
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $lower = mb_strtolower($search);
            $query->where(function ($q) use ($searchColumns, $lower) {
                foreach ($searchColumns as $i => $col) {
                    $method = $i === 0 ? 'whereRaw' : 'orWhereRaw';
                    $q->{$method}("LOWER({$col}) LIKE ?", ["%{$lower}%"]);
                }
            });
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $data = $query->latest()->limit($limit)->get();

        // Render satu partial generik (tanpa file per-tipe) agar kontrak {html} tetap terjaga.
        $html = view('admin.master.partials.generic-table', [
            'type' => $normalized,
            'rows' => $data,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'count' => $data->count(), // Tambahkan informasi jumlah data
        ]);
    }

    public function bulkAction(Request $request, $type)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'selected' => 'required|array',
            'selected.*' => 'required|integer',
        ]);

        $action = $request->input('action');
        $selected = $request->input('selected');
        $count = count($selected);
        $status = false;
        $message = '';

        switch ($type) {
            case 'unit-proses':
                $model = UnitProses::whereIn('id', $selected);
                $typeMessage = 'Unit Proses';
                break;
            case 'departments':
                $model = Department::whereIn('id', $selected);
                $typeMessage = 'Departemen';
                break;
            case 'buildings':
                $model = Building::whereIn('id', $selected);
                $typeMessage = 'Gedung';
                break;
            case 'locations':
                $model = Location::whereIn('id', $selected);
                $typeMessage = 'Lokasi';
                break;
            case 'categories':
                $model = Category::whereIn('id', $selected);
                $typeMessage = 'Kategori';
                break;
            case 'kategori-order':
                $model = KategoriOrder::whereIn('id', $selected);
                $typeMessage = 'Kategori Order';
                break;
            case 'positions':
                $model = Position::whereIn('id', $selected);
                $typeMessage = 'Posisi';
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
            'limit' => 'required|integer|min:5|max:20',
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

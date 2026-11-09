<?php

namespace App\Http\Controllers\Api;

use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
use App\Models\KategoriOrder;
use App\Models\KategoriPengajuan;
use App\Models\Location;
use App\Models\Position;
use App\Models\UnitProses;
use App\Models\User;
use Illuminate\Http\Request;

class LookupController extends BaseApiController
{
    public function categories(Request $request)
    {
        // Unified pengajuan: get all active KategoriPengajuan (all jenis), no jenis filter.
        $query = KategoriPengajuan::with('jenis')->where('status', 1);
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        $items = $query->orderBy('name')->get();

        if ($items->isNotEmpty()) {
            $mapped = $items->map(fn ($k) => [
                'id' => $k->id,
                'name' => $k->name,
                'code' => $k->code ?? null,
                'jenis_id' => $k->jenis_id,
                'jenis' => $k->jenis ? ['id' => $k->jenis->id, 'code' => $k->jenis->code, 'name' => $k->jenis->name] : null,
            ]);
            return $this->success($mapped, 'Categories');
        }

        // Legacy fallback only when unified table has no data
        $legacy = Category::with('unitProses')->where('status', 1);
        if ($request->filled('search')) {
            $legacy->where('name', 'like', "%{$request->search}%");
        }
        return $this->success($legacy->orderBy('name')->get(), 'Categories');
    }

    public function departments(Request $request)
    {
        $query = Department::with(['location', 'building'])->where('status', 1);
        if ($request->filled('search')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($request->search).'%']);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        return $this->success($query->get(), 'Departments');
    }

    public function buildings(Request $request)
    {
        $query = Building::where('status', 1);
        if ($request->filled('search')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($request->search).'%']);
        }

        return $this->success($query->get(), 'Buildings');
    }

    public function locations(Request $request)
    {
        $query = Location::where('status', 1);
        if ($request->filled('search')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($request->search).'%']);
        }

        return $this->success($query->get(), 'Locations');
    }

    public function unitProses(Request $request)
    {
        $query = UnitProses::where('status', 1);
        if ($request->filled('exclude_sirs') && $request->exclude_sirs) {
            $query->where('code', '!=', 'SIRS');
        }
        if ($request->filled('search')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($request->search).'%']);
        }

        return $this->success($query->get(), 'Unit Proses');
    }

    public function positions(Request $request)
    {
        $query = Position::where('status', 1);
        if ($request->filled('search')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($request->search).'%']);
        }

        return $this->success($query->get(), 'Positions');
    }

    public function kategoriOrders(Request $request)
    {
        $query = KategoriOrder::where('status', 1);
        if ($request->filled('search')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($request->search).'%']);
        }

        return $this->success($query->orderBy('name', 'asc')->get(), 'Kategori Order');
    }

    public function priorities()
    {
        return $this->success([
            ['value' => 'low', 'label' => 'Low'],
            ['value' => 'medium', 'label' => 'Medium'],
            ['value' => 'high', 'label' => 'High'],
            ['value' => 'RENDAH', 'label' => 'RENDAH'],
            ['value' => 'SEDANG', 'label' => 'SEDANG'],
            ['value' => 'TINGGI/URGENT', 'label' => 'TINGGI/URGENT'],
        ], 'Priorities');
    }

    public function ticketStatuses()
    {
        return $this->success([
            'open', 'pending', 'in_progress', 'closed', 'confirmed',
        ], 'Ticket Statuses');
    }

    public function orderStatuses()
    {
        return $this->success([
            'open', 'in_progress', 'confirmed', 'rejected',
        ], 'Order Statuses');
    }

    public function users(Request $request)
    {
        $query = User::query()->select(['id', 'name']);
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        $users = $query->orderBy('name')->limit(500)->get()->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
        ]);
        return $this->success($users, 'Users');
    }
}

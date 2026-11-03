<?php

namespace App\Http\Controllers\Api;

use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
use App\Models\KategoriOrder;
use App\Models\Location;
use App\Models\Position;
use App\Models\UnitProses;
use App\Models\User;
use Illuminate\Http\Request;

class LookupController extends BaseApiController
{
    public function categories(Request $request)
    {
        $query = Category::with('unitProses')->where('status', 1);
        if ($request->filled('unit_proses_code')) {
            $query->whereHas('unitProses', fn ($q) => $q->where('code', $request->unit_proses_code));
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        return $this->success($query->get(), 'Categories');
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
        // Semua kategori aktif bisa dipilih siapa pun. Assignment user/unit
        // hanya menentukan PJ order (bukan pembatas pilihan).
        $query = KategoriOrder::where('status', 1);
        if ($request->filled('search')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($request->search).'%']);
        }

        return $this->success($query->orderBy('name', 'asc')->get(), 'Kategori Order');
    }

    /**
     * User yang ditugaskan pada sebuah kategori order (by name).
     * Dipakai picker PJ: hanya user yang ditugaskan yang muncul.
     */
    public function kategoriOrderUsers(Request $request)
    {
        $name = trim((string) $request->get('name', ''));
        if ($name === '') {
            return $this->success([], 'Kategori Order Users');
        }

        $kategori = KategoriOrder::where('name', $name)->where('status', 1)->first();
        $users = $kategori
            ? $kategori->users()->where('users.status', 1)->orderBy('users.name')->get(['users.id', 'users.name'])
            : collect();

        return $this->success($users, 'Kategori Order Users');
    }

    public function users(Request $request)
    {
        $query = User::where('status', 1)->orderBy('name', 'asc');
        if ($request->filled('search')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($request->search).'%']);
        }

        return $this->success($query->get(['id', 'name']), 'Users');
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
}

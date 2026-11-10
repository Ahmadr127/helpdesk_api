<?php

namespace App\Http\Controllers\Api;

use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
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
        // Unified pengajuan: get all active KategoriPengajuan (all jenis) by default.
        // Relasi yang benar adalah jenisList (pivot kategori_jenis). `jenis` hanyalah
        // accessor BC, sehingga eager-load('jenis') akan melempar RelationNotFoundException.
        $query = KategoriPengajuan::with('jenisList')->where('status', 1);
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        // Filter jenis opsional, mis. ?jenis=IT / ?jenis=IPSRS.
        if ($request->filled('jenis')) {
            $jenisCode = strtoupper((string) $request->jenis);
            $query->whereHas('jenisList', fn ($q) => $q->where('code', $jenisCode));
        }
        $items = $query->orderBy('name')->get();

        if ($items->isNotEmpty()) {
            $mapped = $items->map(function ($k) {
                $jenisList = $k->jenisList;

                return [
                    'id' => $k->id,
                    'name' => $k->name,
                    'code' => $k->code ?? null,
                    // BC: kategori kini bisa punya banyak jenis, field ini
                    // mengikuti jenis pertama agar kontrak API lama tetap jalan.
                    'jenis_id' => $k->jenis_id,
                    'jenis' => $jenisList->isNotEmpty()
                        ? [
                            'id' => $jenisList->first()->id,
                            'code' => $jenisList->first()->code,
                            'name' => $jenisList->first()->name,
                        ]
                        : null,
                    'jenis_list' => $jenisList->map(fn ($j) => [
                        'id' => $j->id,
                        'code' => $j->code,
                        'name' => $j->name,
                    ])->values()->all(),
                    'jenis_codes' => $k->jenis_codes,
                ];
            });
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
        // Kategori order (IPSRS/RT) WAJIB memakai scope umum() — helper yang
        // sama dengan web (AdministrasiUmumController). Sebelumnya endpoint ini
        // membaca tabel legacy `kategori_order` yang ID-nya bentrok dengan
        // `kategori_pengajuan`, sehingga order bisa tersimpan dengan kategori
        // yang salah (mis. id 2 = "Pengadaan" legacy vs "Teramedik" unified).
        $query = KategoriPengajuan::with('jenisList')->where('status', 1)->umum();
        if ($request->filled('search')) {
            $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($request->search).'%']);
        }
        if ($request->filled('jenis')) {
            $jenisCode = strtoupper((string) $request->jenis);
            $query->whereHas('jenisList', fn ($q) => $q->where('code', $jenisCode));
        }

        $items = $query->orderBy('name', 'asc')->get();

        return $this->success(
            $items->map(fn ($k) => [
                'id' => $k->id,
                'name' => $k->name,
                'code' => $k->code ?? null,
                'jenis_id' => $k->jenis_id,
                'jenis' => $k->jenisList->isNotEmpty()
                    ? [
                        'id' => $k->jenisList->first()->id,
                        'code' => $k->jenisList->first()->code,
                        'name' => $k->jenisList->first()->name,
                    ]
                    : null,
                'jenis_list' => $k->jenisList->map(fn ($j) => [
                    'id' => $j->id,
                    'code' => $j->code,
                    'name' => $j->name,
                ])->values()->all(),
                'jenis_codes' => $k->jenis_codes,
            ]),
            'Kategori Order'
        );
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
        // Pakai sumber kebenaran tunggal (App\Support\TicketStatus) supaya
        // filter status di mobile lengkap: open, in_progress, ditutup,
        // confirmed, rejected — termasuk label Bahasa Indonesia.
        return $this->success(
            collect(\App\Support\TicketStatus::canonical())
                ->map(fn (string $status) => [
                    'id' => $status,
                    'value' => $status,
                    'name' => \App\Support\TicketStatus::label($status),
                    'label' => \App\Support\TicketStatus::label($status),
                ])
                ->values()
                ->all(),
            'Ticket Statuses'
        );
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

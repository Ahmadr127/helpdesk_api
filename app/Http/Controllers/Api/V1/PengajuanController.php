<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanScopeService;
use App\Services\Pengajuan\PengajuanService;
use Illuminate\Http\Request;

class PengajuanController extends Controller
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanScopeService $scope,
    ) {}

    public function index(Request $request)
    {
        $pengajuans = $this->scope->visibleTo($request->user())
            ->with(['kategori.jenis'])
            ->latest()
            ->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'data' => $pengajuans->items(),
            'meta' => [
                'current_page' => $pengajuans->currentPage(),
                'last_page' => $pengajuans->lastPage(),
                'total' => $pengajuans->total(),
            ],
        ]);
    }

    public function show(Pengajuan $pengajuan)
    {
        $pengajuan->load(['kategori.jenis', 'pemohon', 'historis', 'pesans', 'lampirans']);

        return response()->json(['data' => $pengajuan]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kategori_id' => ['required', 'exists:kategori_pengajuan,id'],
            'deskripsi' => ['required', 'string'],
            'prioritas' => ['nullable', 'in:low,medium,high'],
        ]);

        $pengajuan = $this->service->create($request->user(), $data, []);

        return response()->json(['data' => $pengajuan], 201);
    }
}
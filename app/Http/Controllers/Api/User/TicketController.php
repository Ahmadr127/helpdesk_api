<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\PengajuanResource;
use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanScopeService;
use Illuminate\Http\Request;

class TicketController extends BaseApiController
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanScopeService $scope,
    ) {}

    private function itQuery($user)
    {
        return Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IT'))
            ->where('pemohon_id', $user->id)
            ->with(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building']);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $this->itQuery($request->user())
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar ticket',
            'data' => PengajuanResource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kategori_id' => ['required', 'exists:kategori_pengajuan,id'],
            'description' => 'required_without:deskripsi|nullable|string',
            'deskripsi' => 'required_without:description|nullable|string',
            'priority' => 'required_without:prioritas|nullable|in:low,medium,high',
            'prioritas' => 'required_without:priority|nullable|in:low,medium,high',
            'location_id' => 'nullable|exists:locations,id',
            'building_id' => 'nullable|exists:buildings,id',
            'department_id' => 'nullable|exists:departments,id',
            'photo' => 'nullable|image|max:5120',
        ]);
        try {
            $data = [
                'kategori_id' => $validated['kategori_id'],
                'deskripsi' => $validated['description'] ?? $validated['deskripsi'],
                'prioritas' => $validated['priority'] ?? $validated['prioritas'],
                'department_id' => $validated['department_id'] ?? $request->user()->department_id,
            ];
            if (array_key_exists('location_id', $validated)) $data['lokasi_id'] = $validated['location_id'];
            if (array_key_exists('building_id', $validated)) $data['building_id'] = $validated['building_id'];
            $pengajuan = $this->service->create($request->user(), $data, $request->file('photo') ? [$request->file('photo')] : []);
            $pengajuan->loadMissing(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'historis', 'pesans.sender', 'lampirans']);
            return $this->success(new PengajuanResource($pengajuan), 'Ticket berhasil dibuat', 201);
        } catch (\Exception $e) {
            $code = in_array($e->getCode(), [403, 422]) ? $e->getCode() : 500;
            return $this->error($e->getMessage(), $code);
        }
    }

    public function show(Request $request, Pengajuan $ticket)
    {
        if ((int) $ticket->pemohon_id !== (int) $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }
        $ticket->load(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'historis', 'pesans.sender', 'lampirans']);
        return $this->success(new PengajuanResource($ticket), 'Detail ticket');
    }

    public function update(Request $request, Pengajuan $ticket)
    {
        if ((int) $ticket->pemohon_id !== (int) $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }
        try {
            $validated = $request->validate([
                'description' => 'required_without:deskripsi|nullable|string',
                'deskripsi' => 'required_without:description|nullable|string',
                'priority' => 'required_without:prioritas|nullable|in:low,medium,high',
                'prioritas' => 'required_without:priority|nullable|in:low,medium,high',
                'location_id' => 'nullable|exists:locations,id',
                'building_id' => 'nullable|exists:buildings,id',
                'kategori_id' => 'nullable|exists:kategori_pengajuan,id',
                'photo' => 'nullable|image|max:5120',
            ]);
            $data = [
                'deskripsi' => $validated['description'] ?? $validated['deskripsi'] ?? null,
                'prioritas' => $validated['priority'] ?? $validated['prioritas'] ?? null,
            ];
            if (array_key_exists('location_id', $validated)) $data['lokasi_id'] = $validated['location_id'];
            if (array_key_exists('building_id', $validated)) $data['building_id'] = $validated['building_id'];
            if (! empty($validated['kategori_id'])) $data['kategori_id'] = $validated['kategori_id'];
            $updated = $this->service->updateOwn($request->user(), $ticket, $data, $request->file('photo') ? [$request->file('photo')] : []);
            $updated->loadMissing(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'historis', 'pesans.sender', 'lampirans']);
            return $this->success(new PengajuanResource($updated), 'Ticket berhasil diperbarui');
        } catch (\Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            return $this->error($e->getMessage(), $code);
        }
    }

    public function destroy(Request $request, Pengajuan $ticket)
    {
        if ((int) $ticket->pemohon_id !== (int) $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }
        try {
            $this->service->deleteOwn($request->user(), $ticket);
            return $this->success(null, 'Ticket berhasil dihapus');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function reply(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate(['message' => 'required|string']);
        try {
            $pesan = $this->service->addMessage($request->user(), $ticket, $validated['message'], $request->file('photo') ? [$request->file('photo')] : []);
            $pesan->loadMissing(['sender', 'lampirans']);
            return $this->success($pesan, 'Reply berhasil dikirim');
        } catch (\Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            return $this->error($e->getMessage(), $code);
        }
    }

    public function confirm(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate([
            'confirmation_notes' => 'required|string',
            'action' => 'required|in:confirm,reject',
        ]);
        try {
            $this->service->addMessage($request->user(), $ticket, $validated['confirmation_notes'], []);
            if ($validated['action'] === 'confirm') {
                $ticket->update(['status' => 'confirmed', 'user_confirmation' => true, 'user_confirmed_at' => now(), 'confirmed_at' => now()]);
            } else {
                $ticket->update(['status' => 'in_progress', 'rejection_count' => $ticket->rejection_count + 1, 'user_confirmation' => false, 'user_confirmed_at' => now()]);
            }
            $ticket->load(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'historis', 'pesans.sender', 'lampirans']);
            return $this->success(new PengajuanResource($ticket), $validated['action'] === 'confirm' ? 'Ticket dikonfirmasi selesai' : 'Ticket dikembalikan ke in_progress');
        } catch (\Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500;
            return $this->error($e->getMessage(), $code);
        }
    }

    public function filterByStatus(Request $request, $status)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = $this->itQuery($request->user());
        if ($status !== 'all') {
            $query->where('status', $status === 'ditutup' ? 'ditutup' : $status);
        }
        $paginator = $query->latest()->paginate($perPage);
        return response()->json([
            'success' => true,
            'message' => "Filter status {$status}",
            'data' => PengajuanResource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'status' => $status,
            ],
        ]);
    }
}

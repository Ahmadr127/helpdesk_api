<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\KategoriPengajuan;
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
            ->where('pemohon_id', $user->id);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $this->itQuery($request->user())
            ->with(['kategori.jenis'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar ticket',
            'data' => $paginator->items(),
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
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high',
        ]);
        try {
            $data = [
                'kategori_id' => $validated['kategori_id'],
                'deskripsi' => $validated['description'],
                'prioritas' => $validated['priority'],
                'department_id' => $request->user()->department_id,
            ];
            $pengajuan = $this->service->create($request->user(), $data, $request->file('photo') ? [$request->file('photo')] : []);
            return $this->success($pengajuan, 'Ticket berhasil dibuat', 201);
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
        $ticket->load(['kategori.jenis', 'pemohon', 'historis', 'pesans', 'lampirans']);
        return $this->success($ticket, 'Detail ticket');
    }

    public function update(Request $request, Pengajuan $ticket)
    {
        if ((int) $ticket->pemohon_id !== (int) $request->user()->id) {
            return $this->error('Unauthorized', 403);
        }
        try {
            $validated = $request->validate([
                'description' => 'required|string',
                'priority' => 'required|in:low,medium,high',
            ]);
            $data = ['deskripsi' => $validated['description'], 'prioritas' => $validated['priority']];
            $updated = $this->service->updateOwn($request->user(), $ticket, $data, []);
            return $this->success($updated, 'Ticket berhasil diperbarui');
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
            $updated = $this->service->addMessage($request->user(), $ticket, $validated['message'], []);
            return $this->success($updated, 'Reply berhasil dikirim');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
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
            return $this->success($ticket, $validated['action'] === 'confirm' ? 'Ticket dikonfirmasi selesai' : 'Ticket dikembalikan ke in_progress');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function filterByStatus(Request $request, $status)
    {
        $query = $this->itQuery($request->user());
        if ($status !== 'all') {
            $query->where('status', $status === 'closed' ? 'pending_confirmation' : $status);
        }
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $query->latest()->paginate($perPage);
        return response()->json([
            'success' => true,
            'message' => "Filter status {$status}",
            'data' => $paginator->items(),
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

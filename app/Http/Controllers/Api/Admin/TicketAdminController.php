<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Api\PengajuanResource;
use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanNotificationService;
use App\Services\Pengajuan\PengajuanPenugasanService;
use App\Services\Pengajuan\PengajuanScopeService;
use App\Services\Pengajuan\PengajuanService;
use Illuminate\Http\Request;

class TicketAdminController extends BaseApiController
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanScopeService $scope,
        protected PengajuanPenugasanService $penugasanService,
        protected PengajuanNotificationService $notificationService,
    ) {}

    private function itQuery()
    {
        return Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IT'))
            ->with(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user']);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = $this->itQuery();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%")
                    ->orWhere('pemohon_nama', 'like', "%{$search}%")
                    ->orWhereHas('pemohon', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('start_date')) $query->whereDate('created_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate('created_at', '<=', $request->end_date);
        if ($request->filled('status') && $request->status !== 'all') $query->where('status', $request->status);
        if ($request->filled('prioritas')) $query->where('prioritas', $request->prioritas);
        if ($request->filled('priority')) $query->where('prioritas', $request->priority);

        $paginator = $query->latest()->paginate($perPage);

        $base = Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IT'));
        return response()->json([
            'success' => true,
            'message' => 'Daftar ticket (admin)',
            'data' => PengajuanResource::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'stats' => [
                'totalTickets' => (clone $base)->count(),
                'openTickets' => (clone $base)->where('status', 'open')->count(),
                'inProgressTickets' => (clone $base)->where('status', 'in_progress')->count(),
                'closedTickets' => (clone $base)->whereIn('status', ['ditutup', 'confirmed'])->count(),
            ],
        ]);
    }

    public function show(Pengajuan $ticket)
    {
        $ticket->load(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user', 'historis', 'pesans.sender', 'lampirans']);
        return $this->success(new PengajuanResource($ticket), 'Detail ticket');
    }

    public function respond(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
            'status' => 'required|in:in_progress,pending_confirmation,confirmed,rejected,ditutup,tutup,closed',
            'penanggung_jawab_ids' => 'nullable|array',
            'penanggung_jawab_ids.*' => 'integer|exists:users,id',
        ]);
        $this->service->addMessage($request->user(), $ticket, $validated['notes'], []);
        $status = $validated['status'];
        if (in_array($status, ['closed', 'tutup'], true)) $status = 'ditutup';
        $ticket->update(['status' => $status]);
        $newlyAssigned = [];
        if (array_key_exists('penanggung_jawab_ids', $validated)) {
            $newlyAssigned = $this->penugasanService->sync($ticket, $validated['penanggung_jawab_ids'] ?? [], $request->user());
        }
        $ticket->refresh();
        $this->notificationService->notifyStatusChanged($ticket, $request->user(), 'responded');
        if (!empty($newlyAssigned)) {
            $this->notificationService->notifyAssigned($ticket, $newlyAssigned, $request->user());
        }
        $ticket->load(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user', 'historis', 'pesans.sender', 'lampirans']);
        return $this->success(new PengajuanResource($ticket), 'Response berhasil ditambahkan');
    }

    public function update(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'status' => 'nullable|in:open,in_progress,pending_confirmation,confirmed,rejected,ditutup,tutup,closed',
            'penanggung_jawab_ids' => 'nullable|array',
            'penanggung_jawab_ids.*' => 'integer|exists:users,id',
        ]);
        if (isset($validated['notes'])) $this->service->addMessage($request->user(), $ticket, $validated['notes'], []);
        $statusChanged = false;
        if (isset($validated['status'])) {
            $status = $validated['status'];
            if (in_array($status, ['closed', 'tutup'], true)) $status = 'ditutup';
            $ticket->update(['status' => $status]);
            $statusChanged = true;
        }
        $newlyAssigned = [];
        if (array_key_exists('penanggung_jawab_ids', $validated)) {
            $newlyAssigned = $this->penugasanService->sync($ticket, $validated['penanggung_jawab_ids'] ?? [], $request->user());
        }
        $ticket->refresh();
        if ($statusChanged) {
            $this->notificationService->notifyStatusChanged($ticket, $request->user(), 'status_updated');
        }
        if (!empty($newlyAssigned)) {
            $this->notificationService->notifyAssigned($ticket, $newlyAssigned, $request->user());
        }
        $ticket->load(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user', 'historis', 'pesans.sender', 'lampirans']);
        return $this->success(new PengajuanResource($ticket), 'Ticket diperbarui');
    }

    public function updatePriority(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate([
            'prioritas' => 'required_without:priority|nullable|in:low,medium,high',
            'priority' => 'required_without:prioritas|nullable|in:low,medium,high',
        ]);
        $prioritas = $validated['prioritas'] ?? $validated['priority'];
        $ticket->update(['prioritas' => $prioritas]);
        $ticket->load(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user', 'historis', 'pesans.sender', 'lampirans']);
        return $this->success(new PengajuanResource($ticket), 'Prioritas tiket berhasil diperbarui');
    }

    public function updateSubmission(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate([
            'description' => 'sometimes|required|string',
            'deskripsi' => 'sometimes|required|string',
            'category_id' => 'nullable|integer',
            'kategori_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'location_id' => 'nullable|integer',
            'building_id' => 'nullable|integer',
            'priority' => 'nullable|in:low,medium,high',
            'prioritas' => 'nullable|in:low,medium,high',
            'penanggung_jawab_ids' => 'nullable|array',
            'penanggung_jawab_ids.*' => 'integer|exists:users,id',
        ]);
        $payload = [];
        $desc = $validated['description'] ?? $validated['deskripsi'] ?? null;
        if ($desc !== null) $payload['deskripsi'] = $desc;
        $prio = $validated['priority'] ?? $validated['prioritas'] ?? null;
        if ($prio !== null) $payload['prioritas'] = $prio;
        $cat = $validated['category_id'] ?? $validated['kategori_id'] ?? null;
        if ($cat) $payload['kategori_id'] = $cat;
        if (array_key_exists('department_id', $validated)) $payload['department_id'] = $validated['department_id'];
        if (array_key_exists('location_id', $validated)) $payload['lokasi_id'] = $validated['location_id'];
        if (array_key_exists('building_id', $validated)) $payload['building_id'] = $validated['building_id'];
        if (!empty($payload)) $ticket->update($payload);
        $newlyAssigned = [];
        if (array_key_exists('penanggung_jawab_ids', $validated)) {
            $newlyAssigned = $this->penugasanService->sync($ticket, $validated['penanggung_jawab_ids'] ?? [], $request->user());
        }
        $ticket->refresh();
        if (!empty($newlyAssigned)) {
            $this->notificationService->notifyAssigned($ticket, $newlyAssigned, $request->user());
        }
        $ticket->load(['kategori.jenis', 'pemohon', 'department', 'lokasi', 'building', 'penugasan.user', 'historis', 'pesans.sender', 'lampirans']);
        return $this->success(new PengajuanResource($ticket), 'Tiket diperbarui');
    }

    public function history(Request $request)
    {
        $query = $this->itQuery()->where('status', 'confirmed')->where('user_confirmation', true);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('created_at', '<=', $request->date_to);
        $paginator = $query->orderBy('user_confirmed_at', 'desc')->paginate($request->get('per_page', 15));
        return response()->json(['success' => true, 'message' => 'History ticket confirmed', 'data' => $paginator->items(), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
    }

    public function historyShow(Pengajuan $ticket)
    {
        if ($ticket->status !== 'confirmed') return $this->error('Ticket belum dikonfirmasi', 422);
        $ticket->load(['pemohon', 'lampirans']);
        return $this->success($ticket, 'Detail history');
    }

    public function all(Request $request) { return $this->index($request); }

    public function open(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $this->itQuery()->with('pemohon')->where('status', 'open')->latest()->paginate($perPage);
        return response()->json(['success' => true, 'message' => 'Open tickets', 'data' => $paginator->items(), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
    }

    public function inProgress(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $this->itQuery()->with('pemohon')->where('status', 'in_progress')->latest()->paginate($perPage);
        return response()->json(['success' => true, 'message' => 'In progress tickets', 'data' => $paginator->items(), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
    }

    public function closed(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $paginator = $this->itQuery()->with('pemohon')->where('status', 'ditutup')->where('user_confirmation', false)->latest()->paginate($perPage);
        return response()->json(['success' => true, 'message' => 'Closed tickets (pending confirmation)', 'data' => $paginator->items(), 'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()]]);
    }
}

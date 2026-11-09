<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanScopeService;
use Illuminate\Http\Request;

class TicketAdminController extends BaseApiController
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanScopeService $scope,
    ) {}

    private function itQuery()
    {
        return Pengajuan::whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IT'));
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = $this->itQuery()->with(['pemohon', 'kategori', 'department']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%")
                    ->orWhereHas('pemohon', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('start_date')) $query->whereDate('created_at', '>=', $request->start_date);
        if ($request->filled('end_date')) $query->whereDate('created_at', '<=', $request->end_date);
        if ($request->filled('status') && $request->status !== 'all') $query->where('status', $request->status);

        $paginator = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar ticket (admin)',
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'stats' => [
                'totalTickets' => $this->itQuery()->count(),
                'openTickets' => $this->itQuery()->where('status', 'open')->count(),
                'inProgressTickets' => $this->itQuery()->where('status', 'in_progress')->count(),
                'closedTickets' => $this->itQuery()->whereIn('status', ['ditutup', 'confirmed'])->count(),
            ],
        ]);
    }

    public function show(Pengajuan $ticket)
    {
        $ticket->load(['pemohon', 'lampirans']);
        return $this->success($ticket, 'Detail ticket');
    }

    public function respond(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate(['notes' => 'required|string', 'status' => 'required|in:in_progress,pending_confirmation,confirmed,rejected']);
        $this->service->addMessage($request->user(), $ticket, $validated['notes'], []);
        $ticket->update(['status' => $validated['status']]);
        return $this->success($ticket, 'Response berhasil ditambahkan');
    }

    public function update(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate(['notes' => 'nullable|string', 'status' => 'nullable|in:open,in_progress,pending_confirmation,confirmed,rejected']);
        if (isset($validated['notes'])) $this->service->addMessage($request->user(), $ticket, $validated['notes'], []);
        if (isset($validated['status'])) $ticket->update(['status' => $validated['status']]);
        return $this->success($ticket, 'Ticket diperbarui');
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

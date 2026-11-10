<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanScopeService;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanWorkflowService;
use Illuminate\Http\Request;

class TicketAdminController extends Controller
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanWorkflowService $workflow,
        protected PengajuanScopeService $scope,
    ) {}

    private function itQuery()
    {
        return $this->scope->visibleTo(auth()->user())
            ->sirs();
    }

    public function index(Request $request)
    {
        $totalTickets = $this->itQuery()->count();
        $openTickets = $this->itQuery()->where('status', 'open')->count();
        $inProgressTickets = $this->itQuery()->where('status', 'in_progress')->count();
        $closedTickets = $this->itQuery()->whereIn('status', ['ditutup', 'confirmed'])->count();
        $pendingConfirmationTickets = $this->itQuery()->where('status', 'ditutup')->where('user_confirmation', false)->count();

        $query = $this->itQuery()->with(['pemohon', 'kategori', 'department']);

        try {
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nomor', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%")
                        ->orWhereHas('pemohon', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $tickets = $query->orderBy('created_at', 'desc')
                ->paginate(10)
                ->withQueryString();

        } catch (\Exception $e) {
            $tickets = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 10, 1);
        }

        $highPriorityTickets = collect($tickets->items())->where('prioritas', 'high');
        $mediumPriorityTickets = collect($tickets->items())->where('prioritas', 'medium');
        $lowPriorityTickets = collect($tickets->items())->where('prioritas', 'low');

        return view('admin.tickets.index', compact(
            'tickets', 'totalTickets', 'openTickets', 'inProgressTickets', 'closedTickets',
            'pendingConfirmationTickets', 'highPriorityTickets', 'mediumPriorityTickets', 'lowPriorityTickets'
        ));
    }

    public function show(Pengajuan $ticket)
    {
        $ticket->load(['kategori.jenisList', 'pemohon', 'department', 'lokasi', 'building', 'historis.actor', 'pesans.sender', 'lampirans', 'penugasan.user']);

        $penugasan = app(\App\Services\Pengajuan\PengajuanPenugasanService::class);
        $canAssign = auth()->user()->can('act', $ticket);
        $candidates = $canAssign ? $penugasan->candidates($ticket) : collect();
        $activePj = $penugasan->active($ticket);

        return view('admin.tickets.show', compact('ticket', 'candidates', 'activePj', 'canAssign'));
    }

    public function updateStatus(Request $request, Pengajuan $ticket)
    {
        $status = $request->status;
        $ticket->update(['status' => $status]);
        return redirect()->back()->with('success', 'Status ticket berhasil diperbarui.');
    }

    public function updatePriority(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate([
            'prioritas' => 'required|in:low,medium,high',
        ]);
        $ticket->update(['prioritas' => $validated['prioritas']]);
        return back()->with('success', 'Prioritas tiket berhasil diperbarui.');
    }

    public function history()
    {
        $tickets = $this->itQuery()
            ->where('status', 'confirmed')
            ->where('user_confirmation', true)
            ->orderBy('user_confirmed_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.tickets.history.index', compact('tickets'));
    }

    public function historyShow(Pengajuan $ticket)
    {
        if ($ticket->status !== 'confirmed') {
            return redirect()->route('admin.tickets.history.index')
                ->with('error', 'Ticket belum dikonfirmasi oleh user.');
        }
        return view('admin.tickets.history.show', compact('ticket'));
    }

    public function confirm(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate(['confirmation_notes' => 'required|string']);
        $this->workflow->adminConfirm(auth()->user(), $ticket, $validated['confirmation_notes']);
        return back()->with('success', 'Ticket has been marked as resolved.');
    }

    public function respond(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
            'status' => 'required|in:in_progress,pending_confirmation,confirmed,rejected',
        ]);
        $this->service->addMessage(auth()->user(), $ticket, $validated['notes'], []);
        $status = $validated['status'] === 'pending_confirmation' ? 'ditutup' : $validated['status'];
        if ($status === 'ditutup') {
            try {
                $this->workflow->close(auth()->user(), $ticket->fresh(), $validated['notes']);
            } catch (\Exception $e) {
                if ($e->getCode() === 422) {
                    return back()->with('error', $e->getMessage())->withInput();
                }
                throw $e;
            }
        } else {
            $ticket->update(['status' => $status]);
        }
        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Response berhasil ditambahkan.');
    }

    public function update(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'status' => 'nullable|in:open,in_progress,pending_confirmation,confirmed,rejected',
        ]);
        if (isset($validated['notes'])) {
            $this->service->addMessage(auth()->user(), $ticket, $validated['notes'], []);
        }
        if (isset($validated['status'])) {
            $status = $validated['status'] === 'pending_confirmation' ? 'ditutup' : $validated['status'];
            if ($status === 'ditutup') {
                try {
                    $this->workflow->close(auth()->user(), $ticket, $validated['notes'] ?? null);
                } catch (\Exception $e) {
                    if ($e->getCode() === 422) {
                        return back()->with('error', $e->getMessage())->withInput();
                    }
                    throw $e;
                }
            } elseif ($status === 'in_progress' && $ticket->status === 'open') {
                try {
                    $this->workflow->start(auth()->user(), $ticket->fresh());
                } catch (\Exception $e) {
                    if ($e->getCode() === 403) {
                        return back()->with('error', $e->getMessage())->withInput();
                    }
                    throw $e;
                }
            } else {
                $ticket->update(['status' => $status]);
            }
        }
        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Ticket berhasil diperbarui.');
    }

    public function all(Request $request)
    {
        $query = $this->itQuery()->with(['pemohon', 'kategori', 'department'])->latest();

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('status', 'ditutup')->where('user_confirmation', false);
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->filled('confirmation')) {
            if ($request->confirmation === 'confirmed') {
                $query->where('status', 'confirmed');
            } elseif ($request->confirmation === 'pending') {
                $query->where('status', 'ditutup')->where('user_confirmation', false);
            }
        }

        $tickets = $query->paginate(10)->withQueryString();
        return view('admin.tickets.all', compact('tickets'));
    }

    public function open(Request $request)
    {
        $query = $this->itQuery()->with('pemohon')->where('status', 'open');
        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month)->whereYear('created_at', $request->year ?? now()->year);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        $tickets = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        return view('admin.tickets.open', compact('tickets'));
    }

    public function inProgress(Request $request)
    {
        $query = $this->itQuery()->with('pemohon')->where('status', 'in_progress');
        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month)->whereYear('created_at', $request->year ?? now()->year);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        $tickets = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        return view('admin.tickets.in-progress', compact('tickets'));
    }

    public function closed(Request $request)
    {
        $query = $this->itQuery()->with('pemohon')->where('status', 'ditutup')->where('user_confirmation', false);
        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month)->whereYear('created_at', $request->year ?? now()->year);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        $tickets = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        return view('admin.tickets.closed', compact('tickets'));
    }

    public function exportHistory(Request $request)
    {
        return back()->with('info', 'Export not yet migrated.');
    }

    public function myticket(Request $request)
    {
        $query = $this->itQuery()->with(['pemohon', 'kategori', 'department'])->where('pemohon_id', auth()->id());

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $tickets = $query->latest()->paginate(10)->withQueryString();
        return view('admin.tickets.myticket.index', compact('tickets'));
    }

    public function showMyticket(Request $request, Pengajuan $ticket)
    {
        if ((int) $ticket->pemohon_id !== (int) auth()->id()) {
            abort(403);
        }

        if ($request->wantsJson()) {
            $photo = $ticket->lampirans->where('kind', 'initial')->first();
            return response()->json([
                'ticket_number' => $ticket->nomor,
                'status' => $ticket->status,
                'priority' => $ticket->prioritas,
                'category' => $ticket->kategori?->name,
                'department' => $ticket->department,
                'description' => $ticket->deskripsi,
                'created_at' => $ticket->created_at?->format('d M Y H:i'),
                'photo_url' => $photo ? \Storage::url($photo->path) : null,
            ]);
        }

        if ($request->boolean('modal')) {
            $ticket->load(['kategori.jenisList', 'pemohon', 'department', 'lokasi', 'building', 'historis.actor', 'pesans.sender', 'lampirans', 'penugasan.user']);
            return response(view('admin.tickets.myticket.component.detail-body', compact('ticket'))->render());
        }

        $ticket->load(['kategori.jenisList', 'pemohon', 'department', 'lokasi', 'building', 'historis.actor', 'pesans.sender', 'lampirans', 'penugasan.user']);
        return view('admin.tickets.show', compact('ticket'));
    }

    public function createMyticket()
    {
        if (! auth()->user()->department && ! auth()->user()->department_id) {
            return redirect()->route('user.settings')
                ->with('error', 'Mohon lengkapi data departemen Anda terlebih dahulu di pengaturan profil untuk dapat membuat tiket.');
        }

        $categories = KategoriPengajuan::where('status', 1)->orderBy('name')->get();
        $buildings = \App\Models\Building::where('status', 1)->get();
        $locations = \App\Models\Location::where('status', 1)->get();
        $user = auth()->user();
        $userDepartment = null;
        if (! empty($user->department_id)) {
            $userDepartment = \App\Models\Department::with(['location', 'building'])->find($user->department_id);
        }
        if (! $userDepartment) {
            return redirect()->route('user.settings')
                ->with('error', 'Departemen tidak ditemukan di master data.');
        }

        return view('admin.tickets.myticket.create', compact('categories', 'buildings', 'locations', 'userDepartment'));
    }

    public function storeMyticket(Request $request)
    {
        if (! auth()->user()->department && ! auth()->user()->department_id) {
            return redirect()->route('user.settings')
                ->with('error', 'Mohon lengkapi data departemen Anda terlebih dahulu di pengaturan profil untuk dapat membuat tiket.');
        }

        $validated = $request->validate([
            'kategori_id' => ['required', 'exists:kategori_pengajuan,id'],
            'location_id' => 'nullable|exists:locations,id',
            'building_id' => 'nullable|exists:buildings,id',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'photo' => 'nullable|image|max:5120',
        ]);

        $user = auth()->user();
        $data = [
            'kategori_id' => $validated['kategori_id'],
            'lokasi_id' => $validated['location_id'] ?? null,
            'building_id' => $validated['building_id'] ?? null,
            'deskripsi' => $validated['description'],
            'prioritas' => $validated['priority'],
            'department_id' => $user->department_id,
        ];
        $files = $request->file('photo') ? [$request->file('photo')] : [];
        $this->service->create($user, $data, $files);

        return redirect()->route('admin.tickets.myticket')
            ->with('success', 'Tiket berhasil dibuat.');
    }

    public function editMyticket(Pengajuan $ticket)
    {
        if ((int) $ticket->pemohon_id !== (int) auth()->id()) {
            abort(403);
        }
        if ($ticket->status !== 'open') {
            return redirect()->route('admin.tickets.myticket.show', $ticket)
                ->with('error', 'Tiket hanya bisa diubah saat status open.');
        }

        $categories = KategoriPengajuan::where('status', 1)->orderBy('name')->get();
        $locations = \App\Models\Location::where('status', 1)->get();
        $buildings = \App\Models\Building::where('status', 1)->get();

        $userDepartment = null;
        if (! empty($ticket->department_id)) {
            $userDepartment = \App\Models\Department::with(['location', 'building'])->find($ticket->department_id);
        }

        return view('admin.tickets.myticket.edit', compact('ticket', 'categories', 'locations', 'buildings', 'userDepartment'));
    }

    public function updateMyticket(Request $request, Pengajuan $ticket)
    {
        if ((int) $ticket->pemohon_id !== (int) auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'description' => 'required|string',
            'kategori_id' => 'required|exists:kategori_pengajuan,id',
            'department_id' => 'nullable|exists:departments,id',
            'location_id' => 'nullable|exists:locations,id',
            'building_id' => 'nullable|exists:buildings,id',
            'priority' => 'required|in:low,medium,high',
            'photo' => 'nullable|image|max:5120',
        ]);

        $data = [
            'kategori_id' => $validated['kategori_id'],
            'deskripsi' => $validated['description'],
            'prioritas' => $validated['priority'],
            'lokasi_id' => $validated['location_id'] ?? $ticket->lokasi_id,
            'building_id' => $validated['building_id'] ?? $ticket->building_id,
            'department_id' => $validated['department_id'] ?? $ticket->department_id,
        ];

        $this->service->updateOwn(auth()->user(), $ticket, $data, $request->file('photo') ? [$request->file('photo')] : []);

        return redirect()->route('admin.tickets.myticket')
            ->with('success', 'Tiket berhasil diperbarui.');
    }

    public function destroyMyticket(Pengajuan $ticket)
    {
        if ((int) $ticket->pemohon_id !== (int) auth()->id()) {
            abort(403);
        }
        try {
            $this->service->deleteOwn(auth()->user(), $ticket);
            return redirect()->route('admin.tickets.myticket')
                ->with('success', 'Tiket berhasil dihapus.');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'open')) {
                return redirect()->route('admin.tickets.myticket.show', $ticket)->with('error', $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to delete ticket: '.$e->getMessage()]);
        }
    }
}

<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Services\Pengajuan\PengajuanScopeService;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanWorkflowService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanWorkflowService $workflow,
        protected PengajuanScopeService $scope,
    ) {}

    private function itQuery()
    {
        return Pengajuan::sirs();
    }

    public function index()
    {
        $tickets = $this->itQuery()
            ->where('pemohon_id', auth()->id())
            ->with(['kategori.jenisList'])
            ->orderBy('created_at', 'desc')
            ->get();

        $categories = KategoriPengajuan::where('status', 1)->sirs()->get();

        return view('user.ticket.index', compact('tickets', 'categories'));
    }

    public function create()
    {
        $user = auth()->user();
        if (! $user->department && ! $user->department_id) {
            return redirect()->route('user.settings')
                ->with('error', 'Mohon lengkapi data departemen Anda terlebih dahulu di pengaturan profil untuk dapat membuat tiket.');
        }

        $categories = KategoriPengajuan::where('status', 1)->sirs()->orderBy('name')->get();
        $buildings = \App\Models\Building::where('status', 1)->get();
        $locations = \App\Models\Location::where('status', 1)->get();
        $unitProses = \App\Models\UnitProses::where('status', 1)->where('code', '!=', 'SIRS')->get();

        $user = auth()->user();
        $userDepartment = null;
        if (! empty($user->department_id)) {
            $userDepartment = \App\Models\Department::with(['location', 'building'])->find($user->department_id);
        }
        if (! $userDepartment && $user->department) {
            $deptValue = $user->department;
            $userDepartment = \App\Models\Department::with(['location', 'building'])->where('code', $deptValue)->first()
                ?? \App\Models\Department::with(['location', 'building'])->where('name', $deptValue)->first()
                ?? \App\Models\Department::with(['location', 'building'])->whereRaw('LOWER(code) = LOWER(?)', [$deptValue])->first()
                ?? \App\Models\Department::with(['location', 'building'])->whereRaw('LOWER(name) = LOWER(?)', [$deptValue])->first();
        }

        return view('user.ticket.create', compact('categories', 'buildings', 'locations', 'unitProses', 'userDepartment'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (! $user->department && ! $user->department_id) {
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

        $data = [
            'kategori_id' => $validated['kategori_id'],
            'lokasi_id' => $validated['location_id'] ?? null,
            'building_id' => $validated['building_id'] ?? null,
            'deskripsi' => $validated['description'],
            'prioritas' => $validated['priority'],
            'department_id' => $user->department_id,
        ];

        $files = $request->file('photo') ? [$request->file('photo')] : [];
        $pengajuan = $this->service->create($user, $data, $files);

        return redirect()->route('user.ticket.show', $pengajuan)
            ->with('success', 'Ticket created successfully.');
    }

    public function show(Pengajuan $ticket)
    {
        if ((int) $ticket->pemohon_id !== (int) auth()->id()) {
            abort(403);
        }

        $ticket->load(['kategori.jenisList', 'pemohon', 'department', 'lokasi', 'building', 'historis.actor', 'pesans.sender', 'lampirans', 'penugasan.user']);

        return view('user.ticket.show', compact('ticket'));
    }

    public function edit(Pengajuan $ticket)
    {
        if ((int) $ticket->pemohon_id !== (int) auth()->id()) {
            abort(403);
        }
        if ($ticket->status !== 'open') {
            return redirect()->route('user.ticket.show', $ticket)
                ->with('error', 'Ticket can only be edited when in open status.');
        }

        $categories = KategoriPengajuan::where('status', 1)->sirs()->orderBy('name')->get();
        $locations = \App\Models\Location::where('status', 1)->get();
        $buildings = \App\Models\Building::where('status', 1)->get();

        $userDepartment = null;
        if (! empty($ticket->department_id)) {
            $userDepartment = \App\Models\Department::with(['location', 'building'])->find($ticket->department_id);
        }

        return view('user.ticket.edit', compact('ticket', 'categories', 'locations', 'buildings', 'userDepartment'));
    }

    public function update(Request $request, Pengajuan $ticket)
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

        $files = $request->file('photo') ? [$request->file('photo')] : [];
        $this->service->updateOwn(auth()->user(), $ticket, $data, $files);

        return redirect()->route('user.ticket.show', $ticket)
            ->with('success', 'Ticket has been updated successfully.');
    }

    public function destroy(Pengajuan $ticket)
    {
        if ((int) $ticket->pemohon_id !== (int) auth()->id()) {
            abort(403);
        }
        try {
            $this->service->deleteOwn(auth()->user(), $ticket);
            return redirect()->route('user.dashboard')
                ->with('success', 'Ticket has been deleted successfully.');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Only open') || str_contains($e->getMessage(), 'open')) {
                return redirect()->route('user.ticket.show', $ticket)->with('error', $e->getMessage());
            }
            return back()->withErrors(['error' => 'Failed to delete ticket: '.$e->getMessage()]);
        }
    }

    public function confirm(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate([
            'action' => 'required|in:confirm,reject',
        ]);

        try {
            $this->workflow->userConfirm(auth()->user(), $ticket, null, $validated['action'] === 'confirm', []);
            return redirect()->route('user.ticket.show', $ticket)
                ->with('success', $validated['action'] === 'confirm' ? 'Ticket has been confirmed as completed.' : 'Ticket has been returned to in progress status.');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Unauthorized')) {
                abort(403);
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function reply(Request $request, Pengajuan $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'photo' => 'nullable|image|max:5120',
        ]);

        try {
            $this->service->addMessage(auth()->user(), $ticket, $validated['message'], $request->file('photo') ? [$request->file('photo')] : []);
            return back()->with('success', 'Reply sent successfully.');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Unauthorized')) {
                abort(403);
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function filterByStatus($status, Request $request)
    {
        $baseQuery = $this->itQuery()->where('pemohon_id', auth()->id());

        $totalTickets = $this->itQuery()->where('pemohon_id', auth()->id())->count();
        $openTickets = $this->itQuery()->where('pemohon_id', auth()->id())->where('status', 'open')->count();
        $inProgressTickets = $this->itQuery()->where('pemohon_id', auth()->id())->where('status', 'in_progress')->count();
        $closedTickets = $this->itQuery()->where('pemohon_id', auth()->id())->where('status', 'ditutup')->count();
        $confirmedTickets = $this->itQuery()->where('pemohon_id', auth()->id())->where('status', 'confirmed')->count();

        if ($status !== 'all') {
            $baseQuery->where('status', $status === 'ditutup' ? 'ditutup' : $status);
        }

        if ($request->filled(['start_date', 'end_date'])) {
            $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
            $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
            $baseQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        if ($request->filled('ticket_number')) {
            $baseQuery->where('nomor', 'like', '%'.$request->ticket_number.'%');
        }
        if ($request->filled('priority')) {
            $baseQuery->where('prioritas', $request->priority);
        }

        $tickets = $baseQuery->latest()->paginate(10);

        $categories = KategoriPengajuan::where('status', 1)
            ->sirs()
            ->pluck('name');
        $priorities = ['low', 'medium', 'high'];

        return view('user.ticket.filtered', compact(
            'tickets', 'status', 'totalTickets', 'openTickets', 'inProgressTickets',
            'closedTickets', 'confirmedTickets', 'categories', 'priorities'
        ));
    }
}

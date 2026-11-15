<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use App\Services\Pengajuan\PengajuanListFilter;
use App\Services\Pengajuan\PengajuanScopeService;
use App\Services\Pengajuan\PengajuanService;
use App\Services\Pengajuan\PengajuanStatusService;
use App\Services\Pengajuan\PengajuanWorkflowService;
use App\Support\TicketStatus;
use Illuminate\Http\Request;

class TicketAdminController extends Controller
{
    public function __construct(
        protected PengajuanService $service,
        protected PengajuanWorkflowService $workflow,
        protected PengajuanScopeService $scope,
        protected PengajuanListFilter $filter,
        protected PengajuanStatusService $statusService,
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
            // Filter terpusat; alias lama (search/start_date/end_date) tetap didukung.
            $this->filter->apply($query, $request);

            $tickets = $query->orderBy('created_at', 'desc')
                ->paginate(10)
                ->withQueryString();

        } catch (\Exception $e) {
            $tickets = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 10, 1);
        }

        $highPriorityTickets = collect($tickets->items())->where('prioritas', 'high');
        $mediumPriorityTickets = collect($tickets->items())->where('prioritas', 'medium');
        $lowPriorityTickets = collect($tickets->items())->where('prioritas', 'low');

        return view('admin.tickets.index', array_merge([
            'tickets' => $tickets,
            'totalTickets' => $totalTickets,
            'openTickets' => $openTickets,
            'inProgressTickets' => $inProgressTickets,
            'closedTickets' => $closedTickets,
            'pendingConfirmationTickets' => $pendingConfirmationTickets,
            'highPriorityTickets' => $highPriorityTickets,
            'mediumPriorityTickets' => $mediumPriorityTickets,
            'lowPriorityTickets' => $lowPriorityTickets,
            'filter' => $this->filter,
            'kategoris' => $this->filter->options()['kategoris'],
            'departments' => $this->filter->options()['departments'],
        ]));
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
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,pending_confirmation,confirmed,rejected,ditutup,tutup,closed',
        ]);

        if (! $this->scope->canAct(auth()->user(), $ticket)) {
            return back()->with('error', 'Anda tidak berhak mengubah tiket ini.');
        }

        $changed = $this->applyStatus(auth()->user(), $ticket, $validated['status']);
        $ticket->refresh();

        return redirect()->back()->with(
            'success',
            $changed
                ? 'Status tiket diperbarui menjadi '.TicketStatus::label($ticket->status).'.'
                : 'Status tiket tidak berubah (sudah '.TicketStatus::label($ticket->status).').'
        );
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
            'status' => 'required|in:in_progress,pending_confirmation,confirmed,rejected,ditutup,tutup,closed',
            'photo' => 'nullable|image|max:5120',
        ]);

        if (! $this->scope->canAct(auth()->user(), $ticket)) {
            return back()->with('error', 'Anda tidak berhak mengubah tiket ini.')->withInput();
        }

        try {
            $files = $request->file('photo') ? [$request->file('photo')] : [];
            $this->service->addMessage(auth()->user(), $ticket, $validated['notes'], $files);

            // Satu pintu transisi status (timestamp + histori + notifikasi),
            // sama seperti update() dan API.
            $this->statusService->change(auth()->user(), $ticket, $validated['status'], $validated['notes']);
        } catch (\Exception $e) {
            if (in_array($e->getCode(), [403, 422], true)) {
                return back()->with('error', $e->getMessage())->withInput();
            }
            throw $e;
        }

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Response berhasil ditambahkan.');
    }

    public function update(Request $request, Pengajuan $ticket)
    {
        if (! $this->scope->canAct(auth()->user(), $ticket)) {
            return back()->with('error', 'Anda tidak berhak mengubah tiket ini.')->withInput();
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
            // `status` boleh kosong: tombol "Kirim Balasan" memang tidak
            // mengubah status. Alias lama tetap diterima lalu dinormalisasi.
            'status' => 'nullable|in:open,in_progress,pending_confirmation,confirmed,rejected,ditutup,tutup,closed',
            'action' => 'nullable|in:reply,start,close,confirm,reject',
            'photo' => 'nullable|image|max:5120',
        ]);

        $target = $this->resolveTargetStatus($validated['status'] ?? null, $validated['action'] ?? null);
        $notes = trim((string) ($validated['notes'] ?? ''));
        $hasPhoto = $request->hasFile('photo');

        // Jangan pernah "sukses diam-diam": sebelumnya request tanpa `status`
        // (mis. submit programatik / tombol balasan) tetap flash "berhasil"
        // padahal status tidak berubah sama sekali.
        if ($target === null && $notes === '' && ! $hasPhoto) {
            return back()
                ->with('error', 'Tidak ada yang diperbarui. Pilih aksi (Dalam Proses / Tutup Tiket) atau isi catatan balasan.')
                ->withInput();
        }

        try {
            if ($notes !== '' || $hasPhoto) {
                $this->service->addMessage(
                    auth()->user(),
                    $ticket,
                    $notes !== '' ? $notes : '(lampiran)',
                    $hasPhoto ? [$request->file('photo')] : []
                );
            }

            $changed = $target !== null
                ? $this->applyStatus(auth()->user(), $ticket, $target, $notes !== '' ? $notes : null)
                : false;

            $ticket->refresh();

            $message = match (true) {
                $changed => 'Status tiket diperbarui menjadi '.TicketStatus::label($ticket->status).'.',
                $target !== null => 'Status tiket tidak berubah (sudah '.TicketStatus::label($ticket->status).').',
                default => 'Balasan berhasil dikirim.',
            };

            return redirect()->route('admin.tickets.show', $ticket)->with('success', $message);
        } catch (\Exception $e) {
            return back()
                ->with('error', $e->getMessage() ?: 'Gagal memperbarui tiket.')
                ->withInput();
        }
    }

    /**
     * Terjemahkan input tombol menjadi status kanonik target.
     *
     * `status` (nilai tombol) diprioritaskan; `action` menjadi fallback supaya
     * tombol yang tidak mengirim `status` (mis. "Kirim Balasan") tetap jelas
     * maksudnya. `reply` berarti status tidak diubah.
     */
    protected function resolveTargetStatus(?string $status, ?string $action): ?string
    {
        $normalized = TicketStatus::normalize($status);

        if ($normalized !== null) {
            return $normalized;
        }

        return match ($action) {
            'start' => TicketStatus::IN_PROGRESS,
            'close' => TicketStatus::DITUTUP,
            'confirm' => TicketStatus::CONFIRMED,
            'reject' => TicketStatus::REJECTED,
            default => null,
        };
    }

    /**
     * Terapkan transisi status lewat service terpusat supaya timestamp
     * (started_at/closed_at/confirmed_at/rejected_at) + histori selalu sama
     * dengan alur API dan halaman order perbaikan.
     *
     * @return bool true bila status benar-benar berubah (bukan no-op).
     */
    protected function applyStatus(User $actor, Pengajuan $ticket, string $status, ?string $notes = null): bool
    {
        return $this->statusService->change($actor, $ticket, $status, $notes)['changed'];
    }

    public function all(Request $request)
    {
        $query = $this->itQuery()->with(['pemohon', 'kategori', 'department']);

        // Filter terpusat (semua kolom tabel + pencarian global).
        $this->filter->apply($query, $request);

        $tickets = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.tickets.all', array_merge([
            'tickets' => $tickets,
            'filter' => $this->filter,
            'showConfirmation' => true,
            'showPrioritas' => true,
        ], $this->filter->options()));
    }

    public function open(Request $request)
    {
        $query = $this->itQuery()->with(['pemohon', 'kategori', 'department'])->where('status', 'open');

        // Status dikunci halaman ini (filter status di tabel dinonaktifkan).
        $this->filter->apply($query, $request, 'open');

        $tickets = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.tickets.open', array_merge([
            'tickets' => $tickets,
            'filter' => $this->filter,
            'lockedStatus' => 'open',
        ], $this->filter->options()));
    }

    public function inProgress(Request $request)
    {
        $query = $this->itQuery()->with(['pemohon', 'kategori', 'department'])->where('status', 'in_progress');

        $this->filter->apply($query, $request, 'in_progress');

        $tickets = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.tickets.in-progress', array_merge([
            'tickets' => $tickets,
            'filter' => $this->filter,
            'lockedStatus' => 'in_progress',
        ], $this->filter->options()));
    }

    public function closed(Request $request)
    {
        $query = $this->itQuery()->with(['pemohon', 'kategori', 'department'])
            ->where('status', 'ditutup')
            ->where('user_confirmation', false);

        $this->filter->apply($query, $request, 'ditutup');

        $tickets = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('admin.tickets.closed', array_merge([
            'tickets' => $tickets,
            'filter' => $this->filter,
            'lockedStatus' => 'ditutup',
        ], $this->filter->options()));
    }

    public function exportHistory(Request $request)
    {
        return back()->with('info', 'Export not yet migrated.');
    }

    public function myticket(Request $request)
    {
        $query = $this->itQuery()->with(['pemohon', 'kategori', 'department'])->where('pemohon_id', auth()->id());

        // Filter terpusat; parameter lama (search/status) tetap didukung.
        $this->filter->apply($query, $request);

        $tickets = $query->latest()->paginate(10)->withQueryString();

        return view('admin.tickets.myticket.index', array_merge([
            'tickets' => $tickets,
            'filter' => $this->filter,
        ], $this->filter->options()));
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

        $categories = KategoriPengajuan::where('status', 1)->sirs()->orderBy('name')->get();
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

        $categories = KategoriPengajuan::where('status', 1)->sirs()->orderBy('name')->get();
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

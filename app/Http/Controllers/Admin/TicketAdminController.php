<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TicketsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ticket\AdminConfirmTicketRequest;
use App\Http\Requests\Api\Ticket\AdminRespondRequest;
use App\Http\Requests\Api\Ticket\AdminUpdateRequest;
use App\Http\Requests\Api\Ticket\StoreTicketRequest;
use App\Http\Requests\Api\Ticket\UpdateTicketRequest;
use App\Http\Requests\Api\Ticket\UpdateTicketStatusRequest;
use App\Http\Requests\Api\Ticket\UpdateTicketSubmissionRequest;
use App\Models\Building;
use App\Models\Category;
use App\Models\Location;
use App\Models\Ticket;
use App\Notifications\TicketRespondedNotification;
use App\Services\Api\TicketService;
use App\Services\DepartmentService;
use Illuminate\Http\Request;

class TicketAdminController extends Controller
{
    public function index(Request $request, TicketService $service)
    {
        $stats = $service->statistics();

        $filters = $request->only(['search', 'start_date', 'end_date', 'status']);
        $filters['exclude_confirmed'] = true;
        $filters['sort'] = 'priority';

        try {
            $tickets = $service->listForAdmin($filters, 10)->withQueryString();
        } catch (\Exception $e) {
            // If there's an error, return empty paginator but don't break the page
            $tickets = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]),
                0,
                10,
                1
            );
        }

        // Group tickets by priority for the view
        $highPriorityTickets = $tickets->where('priority', 'high');
        $mediumPriorityTickets = $tickets->where('priority', 'medium');
        $lowPriorityTickets = $tickets->where('priority', 'low');

        return view('admin.tickets.index', array_merge($stats, compact(
            'tickets',
            'highPriorityTickets',
            'mediumPriorityTickets',
            'lowPriorityTickets'
        )));
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['user', 'categoryRelation', 'departmentRelation', 'buildingRelation', 'locationRelation', 'photos', 'penanggungJawab']);

        $categories = Category::where('status', 1)
            ->whereHas('unitProses', function ($query) {
                $query->where('code', 'SIRS');
            })
            ->get();
        $locations = Location::where('status', 1)->get();
        $buildings = Building::where('status', 1)->get();
        $users = \App\Models\User::where('status', 1)->orderBy('name')->get();

        $selectedPjIds = $ticket->penanggungJawab->pluck('id')->toArray();
        if (empty($selectedPjIds)) {
            $selectedPjIds = [auth()->id()];
        }

        return view('admin.tickets.show', compact('ticket', 'categories', 'locations', 'buildings', 'users', 'selectedPjIds'));
    }

    public function updateSubmission(UpdateTicketSubmissionRequest $request, Ticket $ticket, TicketService $service)
    {
        $validated = $request->validated();

        try {
            $service->updateAdminTicket(auth()->user(), $ticket, $validated, $request->file('photo'), $request->input('penanggung_jawab_ids', []));

            return redirect()->route('admin.tickets.show', $ticket)
                ->with('success', 'Pengajuan tiket berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal memperbarui pengajuan: '.$e->getMessage()])->withInput();
        }
    }

    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket)
    {
        $status = $request->validated()['status'];
        $updates = ['status' => $status];
        $message = '';

        switch ($status) {
            case 'in_progress':
                $updates['in_progress_at'] = now();
                $message = "Ticket #{$ticket->ticket_number} sedang dalam proses pengerjaan";
                break;
            case 'closed':
                $updates['closed_at'] = now();
                $updates['status'] = 'pending';
                $message = "Ticket #{$ticket->ticket_number} telah selesai dan menunggu konfirmasi Anda";
                break;
            case 'open':
                $message = "Ticket #{$ticket->ticket_number} telah dibuka kembali";
                break;
        }

        $ticket->update($updates);

        // Send notification to user
        $ticket->user->notify(new TicketRespondedNotification(
            $ticket,
            auth()->user(),
            $message,
            true,
            'updated'
        ));

        return redirect()->back()->with('success', 'Status ticket berhasil diperbarui.');
    }

    public function history(Request $request, TicketService $service)
    {
        $filters = $request->only(['date_from', 'date_to']);

        $tickets = $service->history($filters, 10)->withQueryString();

        return view('admin.tickets.history.index', compact('tickets'));
    }

    public function historyShow(Ticket $ticket)
    {
        // Pastikan ticket sudah dikonfirmasi
        if ($ticket->status !== 'confirmed') {
            return redirect()->route('admin.tickets.history.index')
                ->with('error', 'Ticket belum dikonfirmasi oleh user.');
        }

        return view('admin.tickets.history.show', compact('ticket'));
    }

    public function confirm(AdminConfirmTicketRequest $request, Ticket $ticket)
    {
        $validated = $request->validated();

        $ticket->update([
            'admin_confirmation' => true,
            'admin_confirmed_at' => now(),
            'admin_confirmation_notes' => $validated['confirmation_notes'],
            'status' => 'closed',
        ]);

        return back()->with('success', 'Ticket has been marked as resolved.');
    }

    public function respond(AdminRespondRequest $request, Ticket $ticket, TicketService $service)
    {
        $validated = $request->validated();

        $service->adminRespond(auth()->user(), $ticket, $validated['notes'], $validated['status'], $request->file('photo'));

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Response berhasil ditambahkan.');
    }

    public function update(AdminUpdateRequest $request, Ticket $ticket, TicketService $service)
    {
        $validated = $request->validated();

        $service->adminUpdate(auth()->user(), $ticket, $validated['notes'], $validated['status'] ?? null, $validated['action'] ?? null, $request->file('photo'));

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', $request->input('action') === 'reply' ? 'Reply sent successfully.' : 'Ticket berhasil diperbarui.');
    }

    public function all(Request $request, TicketService $service)
    {
        $filters = $request->only(['status', 'start_date', 'end_date', 'confirmation']);

        $tickets = $service->listForAdmin($filters, 10)->withQueryString();

        return view('admin.tickets.all', compact('tickets'));
    }

    public function open(Request $request, TicketService $service)
    {
        $filters = array_merge(
            $request->only(['search', 'month', 'year', 'date_from', 'date_to']),
            ['status' => 'open']
        );

        $tickets = $service->listForAdmin($filters, 10)->withQueryString();

        return view('admin.tickets.open', compact('tickets'));
    }

    public function inProgress(Request $request, TicketService $service)
    {
        $filters = array_merge(
            $request->only(['search', 'month', 'year', 'date_from', 'date_to']),
            ['status' => 'in_progress']
        );

        $tickets = $service->listForAdmin($filters, 10)->withQueryString();

        return view('admin.tickets.in-progress', compact('tickets'));
    }

    public function closed(Request $request, TicketService $service)
    {
        $filters = array_merge(
            $request->only(['search', 'month', 'year', 'date_from', 'date_to']),
            ['status' => 'closed', 'user_confirmation' => false]
        );

        $tickets = $service->listForAdmin($filters, 10)->withQueryString();

        return view('admin.tickets.closed', compact('tickets'));
    }

    public function exportHistory(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Get selected tickets and ensure they are integers
        $selectedIds = collect($request->input('selected_tickets', []))
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter(function ($id) {
                return $id > 0;
            })
            ->toArray();

        $fileName = 'tickets-history-'.now()->format('Y-m-d').'.xlsx';

        $export = new TicketsExport($dateFrom, $dateTo, $selectedIds);

        return $export->download($fileName);
    }

    /**
     * Tiket milik sendiri (pengaju = user login) di shell admin.
     * Berbeda dari index (semua tiket, butuh permission `ticket` + cakupan admin).
     */
    public function myticket(Request $request)
    {
        $query = Ticket::with(['user'])
            ->where('user_id', auth()->id());

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $tickets = $query->latest()->paginate(10)->withQueryString();

        return view('admin.tickets.myticket.index', compact('tickets'));
    }

    /**
     * Detail tiket milik sendiri (tanpa butuh permission ticket).
     * Kembalikan JSON bila diminta (untuk modal detail).
     */
    public function showMyticket(Request $request, Ticket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        if ($request->wantsJson()) {
            $photo = $ticket->photos->where('type', 'initial')->first();

            return response()->json([
                'ticket_number' => $ticket->ticket_number,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'category' => $ticket->category,
                'department' => $ticket->department,
                'description' => $ticket->description,
                'created_at' => $ticket->created_at?->format('d M Y H:i'),
                'photo_url' => $photo ? \Storage::url($photo->photo_path) : null,
            ]);
        }

        if ($request->boolean('modal')) {
            return response(view('admin.tickets.myticket.component.detail-body', compact('ticket'))->render());
        }

        return view('admin.tickets.show', compact('ticket'));
    }

    /**
     * Form buat tiket milik sendiri di shell admin.
     */
    public function createMyticket(DepartmentService $departments)
    {
        if (! auth()->user()->department) {
            return redirect()->route('user.settings')
                ->with('error', 'Mohon lengkapi data departemen Anda terlebih dahulu di pengaturan profil untuk dapat membuat tiket.');
        }

        $categories = Category::where('status', 1)
            ->whereHas('unitProses', function ($query) {
                $query->where('code', 'SIRS');
            })
            ->get();
        $buildings = Building::where('status', 1)->get();
        $locations = Location::where('status', 1)->get();
        $userDepartment = $departments->resolveForUser(auth()->user());

        if (! $userDepartment) {
            return redirect()->route('user.settings')
                ->with('error', 'Departemen tidak ditemukan di master data. Silakan perbarui departemen Anda di pengaturan profil atau hubungi administrator.');
        }

        return view('admin.tickets.myticket.create', compact('categories', 'buildings', 'locations', 'userDepartment'));
    }

    /**
     * Simpan tiket milik sendiri dari shell admin.
     */
    public function storeMyticket(StoreTicketRequest $request, TicketService $service)
    {
        if (! auth()->user()->department) {
            return redirect()->route('user.settings')
                ->with('error', 'Mohon lengkapi data departemen Anda terlebih dahulu di pengaturan profil untuk dapat membuat tiket.');
        }

        $validated = $request->validated();

        try {
            $service->create(auth()->user(), $validated, $request->file('photo'));

            return redirect()->route('admin.tickets.myticket')
                ->with('success', 'Tiket berhasil dibuat.');
        } catch (\Exception $e) {
            \Log::error('Error creating myticket: '.$e->getMessage());

            return back()->withInput()->with('error', 'Gagal membuat tiket: '.$e->getMessage());
        }
    }

    /**
     * Form ubah tiket milik sendiri (hanya saat open).
     */
    public function editMyticket(Ticket $ticket, DepartmentService $departments)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }
        if ($ticket->status !== 'open') {
            return redirect()->route('admin.tickets.myticket.show', $ticket)
                ->with('error', 'Tiket hanya bisa diubah saat status open.');
        }

        $categories = Category::where('status', 1)
            ->whereHas('unitProses', function ($query) {
                $query->where('code', 'SIRS');
            })
            ->get();
        $locations = Location::where('status', 1)->get();
        $buildings = Building::where('status', 1)->get();
        $userDepartment = $departments->resolveForUser(auth()->user());

        return view('admin.tickets.myticket.edit', compact('ticket', 'categories', 'locations', 'userDepartment', 'buildings'));
    }

    /**
     * Update tiket milik sendiri.
     */
    public function updateMyticket(UpdateTicketRequest $request, Ticket $ticket, TicketService $service)
    {
        $validated = $request->validated();

        try {
            $service->updateUserTicket(auth()->user(), $ticket, $validated, $request->file('photo'));

            return redirect()->route('admin.tickets.myticket.show', $ticket)
                ->with('success', 'Tiket berhasil diperbarui.');
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Unauthorized') || str_contains($msg, '403')) {
                abort(403);
            }

            return back()->withErrors(['error' => 'Gagal memperbarui tiket: '.$msg])->withInput();
        }
    }

    /**
     * Hapus tiket milik sendiri (hanya saat open).
     */
    public function destroyMyticket(Ticket $ticket, TicketService $service)
    {
        try {
            $service->deleteUserTicket(auth()->user(), $ticket);

            return redirect()->route('admin.tickets.myticket')
                ->with('success', 'Tiket berhasil dihapus.');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Unauthorized')) {
                abort(403);
            }

            return back()->withErrors(['error' => 'Gagal menghapus tiket: '.$e->getMessage()]);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Exports\TicketsExport;
use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\Ticket;
use App\Notifications\TicketRespondedNotification;
use App\Services\Api\TicketService;
use Illuminate\Http\Request;

class TicketAdminController extends Controller
{
    public function index(Request $request)
    {
        $totalTickets = Ticket::count();
        $openTickets = Ticket::where('status', 'open')->count();
        $inProgressTickets = Ticket::where('status', 'in_progress')->count();
        $closedTickets = Ticket::whereIn('status', ['closed', 'confirmed'])->count();
        $pendingConfirmationTickets = Ticket::where('status', 'closed')
            ->where('user_confirmation', false)
            ->count();

        $query = Ticket::with(['user', 'category', 'department'])
            ->whereNotIn('status', ['confirmed']);

        try {
            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Apply date range filters
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            // Apply status filter
            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $tickets = $query->orderByRaw("CASE 
                    WHEN status = 'open' THEN 1 
                    WHEN status = 'in_progress' THEN 2
                    WHEN status = 'closed' THEN 3
                    ELSE 4 
                END")
                ->orderByRaw("CASE 
                    WHEN priority = 'high' THEN 1 
                    WHEN priority = 'medium' THEN 2 
                    WHEN priority = 'low' THEN 3 
                    ELSE 4 
                END")
                ->latest()
                ->paginate(10)
                ->withQueryString();

        } catch (\Exception $e) {
            // If there's an error, return empty collection but don't break the page
            $tickets = new \Illuminate\Pagination\LengthAwarePaginator(
                collect([]), // empty collection
                0, // total items
                10, // per page
                1 // current page
            );
        }

        // Get all tickets grouped by priority for the view
        $highPriorityTickets = $tickets->where('priority', 'high');
        $mediumPriorityTickets = $tickets->where('priority', 'medium');
        $lowPriorityTickets = $tickets->where('priority', 'low');

        return view('admin.tickets.index', compact(
            'tickets',
            'totalTickets',
            'openTickets',
            'inProgressTickets',
            'closedTickets',
            'pendingConfirmationTickets',
            'highPriorityTickets',
            'mediumPriorityTickets',
            'lowPriorityTickets'
        ));
    }

    public function show(Ticket $ticket)
    {
        return view('admin.tickets.show', compact('ticket'));
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $status = $request->status;
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

    public function history()
    {
        $query = Ticket::where('status', 'confirmed')
            ->where('user_confirmation', true);

        // Apply date range filters if provided
        if (request('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }
        if (request('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        $tickets = $query->orderBy('user_confirmed_at', 'desc')
            ->paginate(10)
            ->withQueryString(); // This preserves the query parameters in pagination links

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

    public function confirm(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'confirmation_notes' => 'required|string',
        ]);

        $ticket->update([
            'admin_confirmation' => true,
            'admin_confirmed_at' => now(),
            'admin_confirmation_notes' => $validated['confirmation_notes'],
            'status' => 'closed',
        ]);

        return back()->with('success', 'Ticket has been marked as resolved.');
    }

    public function respond(Request $request, Ticket $ticket, TicketService $service)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
            'photo' => 'nullable|image|max:5120', // 5MB max, optional
            'status' => 'required|in:in_progress,closed',
        ]);

        $service->adminRespond(auth()->user(), $ticket, $validated['notes'], $validated['status'], $request->file('photo'));

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Response berhasil ditambahkan.');
    }

    public function update(Request $request, Ticket $ticket, TicketService $service)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
            'photo' => 'nullable|image|max:5120',
            'status' => 'nullable|in:in_progress,closed',
            'action' => 'nullable|in:reply',
        ]);

        $service->adminUpdate(auth()->user(), $ticket, $validated['notes'], $validated['status'] ?? null, $validated['action'] ?? null, $request->file('photo'));

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', $request->input('action') === 'reply' ? 'Reply sent successfully.' : 'Ticket berhasil diperbarui.');
    }

    public function all(Request $request)
    {
        $query = Ticket::with(['user', 'category', 'department'])
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('status', 'closed')
                    ->where('user_confirmation', false);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by confirmation status
        if ($request->filled('confirmation')) {
            if ($request->confirmation === 'confirmed') {
                $query->where('status', 'confirmed');
            } elseif ($request->confirmation === 'pending') {
                $query->where('status', 'closed')
                    ->where('user_confirmation', false);
            }
        }

        $tickets = $query->paginate(10)->withQueryString();

        return view('admin.tickets.all', compact('tickets'));
    }

    public function open(Request $request)
    {
        $query = Ticket::with('user')
            ->where('status', 'open');

        // Filter by date range if provided
        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month)
                ->whereYear('created_at', $request->year ?? now()->year);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $tickets = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.tickets.open', compact('tickets'));
    }

    public function inProgress(Request $request)
    {
        $query = Ticket::with('user')
            ->where('status', 'in_progress');

        // Filter by date range if provided
        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month)
                ->whereYear('created_at', $request->year ?? now()->year);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $tickets = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.tickets.in-progress', compact('tickets'));
    }

    public function closed(Request $request)
    {
        $query = Ticket::with('user')
            ->where('status', 'closed')
            ->where('user_confirmation', false);

        // Filter by date range if provided
        if ($request->filled('month')) {
            $query->whereMonth('created_at', $request->month)
                ->whereYear('created_at', $request->year ?? now()->year);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $tickets = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

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
        $query = Ticket::with(['user', 'category', 'department'])
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
    public function createMyticket()
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
        $userDepartment = $this->resolveMyticketDepartment(auth()->user());

        if (! $userDepartment) {
            return redirect()->route('user.settings')
                ->with('error', 'Departemen tidak ditemukan di master data. Silakan perbarui departemen Anda di pengaturan profil atau hubungi administrator.');
        }

        return view('admin.tickets.myticket.create', compact('categories', 'buildings', 'locations', 'userDepartment'));
    }

    /**
     * Simpan tiket milik sendiri dari shell admin.
     */
    public function storeMyticket(Request $request, TicketService $service)
    {
        if (! auth()->user()->department) {
            return redirect()->route('user.settings')
                ->with('error', 'Mohon lengkapi data departemen Anda terlebih dahulu di pengaturan profil untuk dapat membuat tiket.');
        }

        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    $category = Category::with('unitProses')->find($value);
                    if (! $category || $category->unitProses->code !== 'SIRS') {
                        $fail('Kategori yang dipilih harus kategori dari unit SIRS.');
                    }
                },
            ],
            'location_id' => 'nullable|exists:locations,id',
            'building_id' => 'nullable|exists:buildings,id',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'photo' => 'nullable|image|max:5120',
        ]);

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
    public function editMyticket(Ticket $ticket)
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
        $userDepartment = $this->resolveMyticketDepartment(auth()->user());

        return view('admin.tickets.myticket.edit', compact('ticket', 'categories', 'locations', 'userDepartment', 'buildings'));
    }

    /**
     * Update tiket milik sendiri.
     */
    public function updateMyticket(Request $request, Ticket $ticket, TicketService $service)
    {
        $validated = $request->validate([
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'department_id' => 'required|exists:departments,id',
            'location_id' => 'nullable|exists:locations,id',
            'building_id' => 'nullable|exists:buildings,id',
            'priority' => 'required|in:low,medium,high',
            'photo' => 'nullable|image|max:5120',
        ]);

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

    protected function resolveMyticketDepartment($user): ?Department
    {
        if (! empty($user->department_id)) {
            $dept = Department::with(['location', 'building'])->find($user->department_id);
            if ($dept) {
                return $dept;
            }
        }
        if ($user->department) {
            $deptValue = $user->department;

            return Department::with(['location', 'building'])->where('code', $deptValue)->first()
                ?? Department::with(['location', 'building'])->where('name', $deptValue)->first()
                ?? Department::with(['location', 'building'])->whereRaw('LOWER(code) = LOWER(?)', [$deptValue])->first()
                ?? Department::with(['location', 'building'])->whereRaw('LOWER(name) = LOWER(?)', [$deptValue])->first();
        }

        return null;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketPhoto;
use App\Exports\TicketsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\TicketRespondedNotification;
use App\Notifications\TicketUpdatedNotification;

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
                $query->where(function($q) use ($search) {
                    $q->where('ticket_number', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('user', function($q) use ($search) {
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
            'confirmation_notes' => 'required|string'
        ]);

        $ticket->update([
            'admin_confirmation' => true,
            'admin_confirmed_at' => now(),
            'admin_confirmation_notes' => $validated['confirmation_notes'],
            'status' => 'closed'
        ]);

        return back()->with('success', 'Ticket has been marked as resolved.');
    }

    public function respond(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
            'photo' => 'nullable|image|max:5120', // 5MB max, optional
            'status' => 'required|in:in_progress,closed'
        ]);

        $response = [
            'notes' => $validated['notes'],
            'timestamp' => now(),
            'status' => $validated['status']
        ];

        // Get existing responses or initialize empty array
        $responses = json_decode($ticket->admin_responses, true) ?? [];

        // Handle photo upload if provided
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('ticket-responses', 'public');
            
            // Create ticket photo record
            TicketPhoto::create([
                'ticket_id' => $ticket->id,
                'photo_path' => $path,
                'type' => 'admin_response'
            ]);
            
            $response['photo'] = $path;
        }

        // Add new response to array
        $responses[] = $response;

        // Update ticket
        $ticket->update([
            'admin_responses' => json_encode($responses),
            'status' => $validated['status'],
            'in_progress_at' => $validated['status'] === 'in_progress' ? now() : $ticket->in_progress_at,
            'closed_at' => $validated['status'] === 'closed' ? now() : $ticket->closed_at,
        ]);

        // Send notification to user
        $ticket->user->notify(new TicketRespondedNotification(
            $ticket,
            auth()->user(),
            $validated['notes'],
            true,
            'responded'
        ));

        return redirect()->route('admin.tickets.show', $ticket)
            ->with('success', 'Response berhasil ditambahkan.');
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'notes' => 'required|string',
            'photo' => 'nullable|image|max:5120',
            'status' => 'nullable|in:in_progress,closed',
            'action' => 'nullable|in:reply'
        ]);

        try {
            DB::beginTransaction();

            // Get existing responses or initialize
            $responses = $ticket->admin_responses ? json_decode($ticket->admin_responses, true) : [];
            $timestamp = now();

            // Create new response
            $response = [
                'notes' => $validated['notes'],
                'timestamp' => $timestamp->toDateTimeString(),
            ];

            // Handle photo
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('ticket-responses', 'public');
                
                TicketPhoto::create([
                    'ticket_id' => $ticket->id,
                    'photo_path' => $path,
                    'type' => 'admin_response',
                    'created_at' => $timestamp
                ]);
                
                $response['photo'] = $path;
            }

            // Add response to array
            $responses[] = $response;

            // Update ticket based on action
            if ($request->action === 'reply') {
                // Just add the reply without changing status
                $ticket->update([
                    'admin_responses' => json_encode($responses),
                ]);
            } else {
                // Handle status change
                $ticket->update([
                    'admin_responses' => json_encode($responses),
                    'status' => $validated['status'],
                    'in_progress_at' => $validated['status'] === 'in_progress' ? $timestamp : $ticket->in_progress_at,
                    'closed_at' => $validated['status'] === 'closed' ? $timestamp : $ticket->closed_at,
                ]);
            }

            // Send notification to user
            $ticket->user->notify(new TicketRespondedNotification(
                $ticket,
                auth()->user(),
                $validated['notes'],
                true,
                $request->action === 'reply' ? 'replied' : 'updated'
            ));

            DB::commit();
            return redirect()->route('admin.tickets.show', $ticket)
                ->with('success', $request->action === 'reply' ? 'Reply sent successfully.' : 'Ticket berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Terjadi kesalahan saat memperbarui ticket.');
        }
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

        $fileName = 'tickets-history-' . now()->format('Y-m-d') . '.xlsx';
        
        $export = new TicketsExport($dateFrom, $dateTo, $selectedIds);
        return $export->download($fileName);
    }
} 
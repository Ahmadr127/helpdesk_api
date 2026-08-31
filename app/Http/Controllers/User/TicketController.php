<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Department;
use App\Models\Category;
use App\Models\Building;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TicketConfirmedNotification;
use App\Models\User;
use App\Notifications\TicketRejectedNotification;
use App\Models\TicketPhoto;
use App\Notifications\TicketRespondedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Get categories from both SIRS and IPSRS units
        $categories = Category::where('status', 1)
            ->whereHas('unitProses', function($query) {
                $query->whereIn('code', ['SIRS', 'IPSRS']);
            })
            ->get();
        
        return view('user.ticket.index', compact('tickets', 'categories'));
    }

    public function create()
    {
        // Check if user has a department
        if (!auth()->user()->department) {
            return redirect()->route('user.settings')
                ->with('error', 'Mohon lengkapi data departemen Anda terlebih dahulu di pengaturan profil untuk dapat membuat tiket.');
        }

        // Get only active categories from SIRS unit
        $categories = Category::where('status', 1)
                            ->whereHas('unitProses', function($query) {
                                $query->where('code', 'SIRS');
                            })
                            ->get();
        $buildings = Building::where('status', 1)->get();
        $locations = Location::where('status', 1)->with('building')->get();

        // Get the authenticated user's department
        $userDepartment = Department::where('code', auth()->user()->department)->first();

        return view('user.ticket.create', compact('categories', 'buildings', 'locations', 'userDepartment'));
    }

    public function store(Request $request)
    {
        // Check if user has a department
        if (!auth()->user()->department) {
            return redirect()->route('user.settings')
                ->with('error', 'Mohon lengkapi data departemen Anda terlebih dahulu di pengaturan profil untuk dapat membuat tiket.');
        }

        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    $category = Category::with('unitProses')->find($value);
                    if (!$category || $category->unitProses->code !== 'SIRS') {
                        $fail('Kategori yang dipilih harus kategori dari unit SIRS.');
                    }
                }
            ],
            'location_id' => 'required|exists:locations,id',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'photo' => 'nullable|image|max:5120', // 5MB max, optional
        ]);

        try {
            DB::beginTransaction();

            // Get the authenticated user's department
            $userDepartment = Department::where('code', auth()->user()->department)->first();
            
            if (!$userDepartment) {
                throw new \Exception('Data departemen tidak ditemukan. Mohon perbarui profil Anda.');
            }
            
            // Get the location and its associated building
            $location = Location::with('building')->findOrFail($validated['location_id']);

            // Generate ticket number
            $date = date('dm'); // Format: DDMM (tanggal dan bulan saja)
            
            // Get the last ticket number for today
            $lastTicket = Ticket::where('ticket_number', 'like', "T-{$date}-%")
                ->orderBy('ticket_number', 'desc')
                ->first();
            
            if ($lastTicket) {
                // Extract the sequence number and increment it
                $lastSequence = (int) substr($lastTicket->ticket_number, -3);
                $sequence = str_pad($lastSequence + 1, 3, '0', STR_PAD_LEFT);
            } else {
                // If no ticket exists for today, start with 001
                $sequence = '001';
            }
            
            $ticketNumber = "T-{$date}-{$sequence}";

            // Get display names from related models
            $category = Category::find($validated['category_id']);

            $ticket = Ticket::create([
                'user_id' => auth()->id(),
                'ticket_number' => $ticketNumber,
                'description' => $validated['description'],
                'category_id' => $validated['category_id'],
                'category' => $category->name,
                'department_id' => $userDepartment->id,
                'department' => $userDepartment->name,
                'building_id' => $location->building->id,
                'building' => $location->building->name,
                'location_id' => $location->id,
                'location' => $location->name,
                'priority' => $validated['priority'],
                'status' => 'open'
            ]);

            // Handle photo upload if provided
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                
                // Generate unique filename with original extension
                $extension = $photo->getClientOriginalExtension();
                $filename = 'ticket_' . uniqid() . '_' . time() . '.' . $extension;
                    
                // Store the file in the public disk
                $path = $photo->storeAs('ticket-photos', $filename, 'public');
            
                // Create ticket photo record with the correct path
                        $photoRecord = TicketPhoto::create([
                            'ticket_id' => $ticket->id,
                            'photo_path' => $path,
                            'type' => 'initial'
                        ]);
                        
                \Log::info('Photo upload successful', [
                            'photo_id' => $photoRecord->id,
                            'ticket_id' => $ticket->id,
                    'path' => $path,
                    'full_path' => storage_path('app/public/' . $path),
                    'exists' => Storage::disk('public')->exists($path)
                    ]);
            }

            DB::commit();
            return redirect()->route('user.ticket.index')
                ->with('success', 'Ticket created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creating ticket: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', 'Failed to create ticket. Please try again. Error: ' . $e->getMessage());
        }
    }

    public function show(Ticket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        return view('user.ticket.show', compact('ticket'));
    }

    public function edit(Ticket $ticket)
    {
        // Check if user owns the ticket
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        // Check if ticket is still editable (open status)
        if ($ticket->status !== 'open') {
            return redirect()->route('user.ticket.show', $ticket)
                ->with('error', 'Ticket can only be edited when in open status.');
        }

        // Get only active categories from SIRS unit
        $categories = Category::where('status', 1)
            ->whereHas('unitProses', function($query) {
                $query->where('code', 'SIRS');
            })
            ->get();
            
        $locations = Location::where('status', 1)->with('building')->get();

        return view('user.ticket.edit', compact('ticket', 'categories', 'locations'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        // Check if user owns the ticket
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        // Check if ticket is still editable (open status)
        if ($ticket->status !== 'open') {
            return redirect()->route('user.ticket.show', $ticket)
                ->with('error', 'Ticket can only be edited when in open status.');
        }

        $validated = $request->validate([
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'department_id' => 'required|exists:departments,id',
            'location_id' => 'required|exists:locations,id',
            'priority' => 'required|in:low,medium,high',
            'photo' => 'nullable|image|max:5120', // 5MB max
        ]);

        try {
            DB::beginTransaction();

            // Get related models
            $category = Category::findOrFail($validated['category_id']);
            $department = Department::findOrFail($validated['department_id']);
            $location = Location::with('building')->findOrFail($validated['location_id']);

            $ticket->update([
                'description' => $validated['description'],
                'category_id' => $validated['category_id'],
                'category' => $category->name,
                'department_id' => $validated['department_id'],
                'department' => $department->name,
                'building_id' => $location->building->id,
                'building' => $location->building->name,
                'location_id' => $validated['location_id'],
                'location' => $location->name,
                'priority' => $validated['priority']
            ]);

            // Handle photo update if provided
            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                $oldPhoto = $ticket->photos()->where('type', 'initial')->first();
                if ($oldPhoto) {
                    Storage::disk('public')->delete($oldPhoto->photo_path);
                    $oldPhoto->delete();
                }

                // Upload new photo
                $photo = $request->file('photo');
                $extension = $photo->getClientOriginalExtension();
                $filename = 'ticket_' . uniqid() . '_' . time() . '.' . $extension;
                $path = $photo->storeAs('ticket-photos', $filename, 'public');

                // Create new photo record
                TicketPhoto::create([
                    'ticket_id' => $ticket->id,
                    'photo_path' => $path,
                    'type' => 'initial'
                ]);
            }

            DB::commit();
            return redirect()->route('user.ticket.show', $ticket)
                ->with('success', 'Ticket has been updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Ticket update error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update ticket: ' . $e->getMessage()])->withInput();
        }
    }

    public function updateStatus(Ticket $ticket, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,open,in_progress,closed'
        ]);

        $timestamp_field = null;
        switch($validated['status']) {
            case 'open':
                $timestamp_field = 'opened_at';
                break;
            case 'in_progress':
                $timestamp_field = 'in_progress_at';
                break;
            case 'closed':
                $timestamp_field = 'closed_at';
                break;
        }

        $ticket->update([
            'status' => $validated['status'],
            $timestamp_field => now()
        ]);

        return back()->with('success', 'Ticket status updated successfully.');
    }

    public function confirm(Request $request, Ticket $ticket)
    {
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'confirmation_notes' => 'required|string',
            'photo' => 'nullable|image|max:5120',
        ]);

        $replies = $ticket->user_replies ? json_decode($ticket->user_replies, true) : [];
        
        $reply = [
            'type' => $request->input('action'), // 'confirm' or 'reject'
            'notes' => $validated['confirmation_notes'],
            'timestamp' => now()->toDateTimeString(),
        ];

        // Save response photo if exists
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('ticket-responses', 'public');
            $reply['photo'] = $path; // Add photo path to reply array
            
            TicketPhoto::create([
                'ticket_id' => $ticket->id,
                'photo_path' => $path,
                'type' => $request->input('action') === 'confirm' ? 'user_response' : 'user_rejection'
            ]);
        }

        $replies[] = $reply;
        $ticket->user_replies = json_encode($replies);

        if ($request->input('action') === 'confirm') {
            $ticket->update([
                'status' => 'confirmed',
                'user_confirmation' => true,
                'user_confirmed_at' => now(),
            ]);

            // Send notification to all admins
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new TicketRespondedNotification(
                    $ticket,
                    auth()->user(),
                    "User has confirmed ticket #{$ticket->ticket_number} as completed",
                    false,
                    'confirmed'
                ));
            }
        } else {
            $ticket->update([
                'status' => 'in_progress',
                'user_confirmation' => false,
                'rejection_count' => $ticket->rejection_count + 1,
                'last_rejection_at' => now(),
            ]);

            // Send notification to all admins
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new TicketRespondedNotification(
                    $ticket,
                    auth()->user(),
                    "User has rejected ticket #{$ticket->ticket_number}",
                    false,
                    'rejected'
                ));
            }
        }

        $ticket->save();

        return redirect()->route('user.ticket.show', $ticket)
            ->with('success', $request->input('action') === 'confirm' ? 
                'Ticket has been confirmed as completed.' : 
                'Ticket has been returned to in progress status.');
    }

    public function reply(Request $request, Ticket $ticket)
    {
        // Validate ownership
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string',
            'photo' => 'nullable|image|max:5120', // 5MB max
        ]);

        $reply = [
            'message' => $validated['message'],
            'timestamp' => now(),
        ];

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('ticket-replies', 'public');
            $reply['photo'] = $path;
        }

        // Get existing replies or create new array
        $replies = $ticket->user_replies ? json_decode($ticket->user_replies, true) : [];
        $replies[] = $reply;

        $ticket->update([
            'user_replies' => json_encode($replies)
        ]);

        // Send notification to all admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new TicketRespondedNotification(
                $ticket,
                auth()->user(),
                "User has replied to ticket #{$ticket->ticket_number}",
                false,
                'replied'
            ));
        }

        return back()->with('success', 'Reply sent successfully.');
    }

    public function filterByStatus($status = 'all', Request $request)
    {
        // Base query for tickets belonging to current user
        $baseQuery = Ticket::where('user_id', auth()->id());

        // Get ticket counts for statistics
        $totalTickets = Ticket::where('user_id', auth()->id())->count();
        $openTickets = Ticket::where('user_id', auth()->id())->where('status', 'open')->count();
        $inProgressTickets = Ticket::where('user_id', auth()->id())->where('status', 'in_progress')->count();
        $closedTickets = Ticket::where('user_id', auth()->id())->where('status', 'closed')->count();
        $confirmedTickets = Ticket::where('user_id', auth()->id())->where('status', 'confirmed')->count();

        // Reset the base query
        $baseQuery = Ticket::where('user_id', auth()->id());

        // Apply status filter if not 'all'
        if ($status !== 'all') {
            $baseQuery->where('status', $status);
        }

        // Apply date range filter if provided
        if ($request->filled(['start_date', 'end_date'])) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $baseQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Apply search filters if provided
        if ($request->filled('ticket_number')) {
            $baseQuery->where('ticket_number', 'like', '%' . $request->ticket_number . '%');
        }
        if ($request->filled('category')) {
            $baseQuery->where('category', $request->category);
        }
        if ($request->filled('priority')) {
            $baseQuery->where('priority', $request->priority);
        }
        if ($request->filled('department')) {
            $baseQuery->where('department', $request->department);
        }

        // Get the filtered tickets with pagination
        $tickets = $baseQuery->latest()->paginate(10);

        // Get categories only from SIRS unit
        $categories = Category::where('status', 1)
            ->whereHas('unitProses', function($query) {
                $query->where('code', 'SIRS');
            })
            ->pluck('name');

        // Get unique departments and set priorities
        $departments = Ticket::distinct()->pluck('department');
        $priorities = ['low', 'medium', 'high'];

        return view('user.ticket.filtered', compact(
            'tickets',
            'status',
            'totalTickets',
            'openTickets',
            'inProgressTickets',
            'closedTickets',
            'confirmedTickets',
            'categories',
            'departments',
            'priorities'
        ));
    }

    public function destroy(Ticket $ticket)
    {
        // Check if user owns the ticket
        if ($ticket->user_id !== auth()->id()) {
            abort(403);
        }

        // Check if ticket can be deleted (only open tickets)
        if ($ticket->status !== 'open') {
            return redirect()->route('user.ticket.show', $ticket)
                ->with('error', 'Only open tickets can be deleted.');
        }

        try {
            $ticket->delete();
            return redirect()->route('user.dashboard')
                ->with('success', 'Ticket has been deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Ticket deletion error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to delete ticket.']);
        }
    }
} 
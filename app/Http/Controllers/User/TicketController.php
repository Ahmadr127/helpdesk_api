<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Api\TicketService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        // Get categories from both SIRS and IPSRS units
        $categories = Category::where('status', 1)
            ->whereHas('unitProses', function ($query) {
                $query->whereIn('code', ['SIRS', 'IPSRS']);
            })
            ->get();

        return view('user.ticket.index', compact('tickets', 'categories'));
    }

    public function create()
    {
        // Check if user has a department
        if (! auth()->user()->department) {
            return redirect()->route('user.settings')
                ->with('error', 'Mohon lengkapi data departemen Anda terlebih dahulu di pengaturan profil untuk dapat membuat tiket.');
        }

        // Get only active categories from SIRS unit
        $categories = Category::where('status', 1)
            ->whereHas('unitProses', function ($query) {
                $query->where('code', 'SIRS');
            })
            ->get();
        $buildings = Building::where('status', 1)->get();
        $locations = Location::where('status', 1)->get();

        // Get the authenticated user's department — robust lookup (support code, name, case-insensitive, and department_id)
        $user = auth()->user();
        $deptValue = $user->department;
        $userDepartment = null;

        // Prefer department_id FK if present and valid
        if (! empty($user->department_id)) {
            $userDepartment = Department::with(['location', 'building'])->find($user->department_id);
        }

        if (! $userDepartment && $deptValue) {
            $userDepartment = Department::with(['location', 'building'])->where('code', $deptValue)->first()
                ?? Department::with(['location', 'building'])->where('name', $deptValue)->first()
                ?? Department::with(['location', 'building'])->whereRaw('LOWER(code) = LOWER(?)', [$deptValue])->first()
                ?? Department::with(['location', 'building'])->whereRaw('LOWER(name) = LOWER(?)', [$deptValue])->first();
        }

        // Graceful fallback instead of 500: redirect to settings if department not resolvable
        if (! $userDepartment) {
            // Auto-create missing department on-the-fly to unblock user (code=name=deptValue)
            // Only if deptValue looks like a valid identifier and not empty
            if ($deptValue && is_string($deptValue)) {
                try {
                    // Avoid duplicate code — check again case-insensitive
                    $exists = Department::with(['location', 'building'])->whereRaw('LOWER(code) = LOWER(?)', [$deptValue])
                        ->orWhereRaw('LOWER(name) = LOWER(?)', [$deptValue])->first();
                    if ($exists) {
                        $userDepartment = $exists;
                    } else {
                        $userDepartment = Department::create([
                            'code' => strtoupper(str_replace(' ', '_', trim($deptValue))),
                            'name' => trim($deptValue),
                            'status' => 1,
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Auto-create department failed for value '.$deptValue.': '.$e->getMessage());
                }
            }
        }

        if (! $userDepartment) {
            return redirect()->route('user.settings')
                ->with('error', 'Departemen "'.e($deptValue).'" tidak ditemukan di master data. Silakan perbarui departemen Anda di pengaturan profil atau hubungi administrator.');
        }

        return view('user.ticket.create', compact('categories', 'buildings', 'locations', 'userDepartment'));
    }

    public function store(Request $request, TicketService $service)
    {
        // Check if user has a department
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
            'photo' => 'nullable|image|max:5120', // 5MB max, optional
        ]);

        try {
            $ticket = $service->create(auth()->user(), $validated, $request->file('photo'));

            return redirect()->route('user.ticket.index')
                ->with('success', 'Ticket created successfully.');
        } catch (\Exception $e) {
            \Log::error('Error creating ticket: '.$e->getMessage());
            $code = $e->getCode();
            if ($code === 422 || str_contains($e->getMessage(), 'SIRS')) {
                return back()->withInput()->with('error', $e->getMessage());
            }

            return back()
                ->withInput()
                ->with('error', 'Failed to create ticket. Please try again. Error: '.$e->getMessage());
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
            ->whereHas('unitProses', function ($query) {
                $query->where('code', 'SIRS');
            })
            ->get();

        $locations = Location::where('status', 1)->get();
        $buildings = Building::where('status', 1)->get();

        $userDepartment = $this->resolveUserDepartment(auth()->user());

        return view('user.ticket.edit', compact('ticket', 'categories', 'locations', 'userDepartment', 'buildings'));
    }

    protected function resolveUserDepartment(User $user): ?Department
    {
        $dept = null;
        if (! empty($user->department_id)) {
            $dept = Department::with(['location', 'building'])->find($user->department_id);
        }
        if (! $dept && $user->department) {
            $deptValue = $user->department;
            $dept = Department::with(['location', 'building'])->where('code', $deptValue)->first()
                ?? Department::with(['location', 'building'])->where('name', $deptValue)->first()
                ?? Department::with(['location', 'building'])->whereRaw('LOWER(code) = LOWER(?)', [$deptValue])->first()
                ?? Department::with(['location', 'building'])->whereRaw('LOWER(name) = LOWER(?)', [$deptValue])->first();
        }

        return $dept;
    }

    public function update(Request $request, Ticket $ticket, TicketService $service)
    {
        $validated = $request->validate([
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'department_id' => 'required|exists:departments,id',
            'location_id' => 'nullable|exists:locations,id',
            'building_id' => 'nullable|exists:buildings,id',
            'priority' => 'required|in:low,medium,high',
            'photo' => 'nullable|image|max:5120', // 5MB max
        ]);

        try {
            $ticket = $service->updateUserTicket(auth()->user(), $ticket, $validated, $request->file('photo'));

            return redirect()->route('user.ticket.show', $ticket)
                ->with('success', 'Ticket has been updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Ticket update error: '.$e->getMessage());
            $msg = $e->getMessage();
            if (str_contains($msg, 'Unauthorized') || str_contains($msg, '403')) {
                abort(403);
            }

            return back()->withErrors(['error' => 'Failed to update ticket: '.$msg])->withInput();
        }
    }

    public function updateStatus(Ticket $ticket, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,open,in_progress,closed',
        ]);

        $timestamp_field = null;
        switch ($validated['status']) {
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
            $timestamp_field => now(),
        ]);

        return back()->with('success', 'Ticket status updated successfully.');
    }

    public function confirm(Request $request, Ticket $ticket, TicketService $service)
    {
        $validated = $request->validate([
            'confirmation_notes' => 'required|string',
            'photo' => 'nullable|image|max:5120',
            'action' => 'required|in:confirm,reject',
        ]);

        try {
            $service->confirm(auth()->user(), $ticket, $validated['confirmation_notes'], $validated['action'], $request->file('photo'));

            return redirect()->route('user.ticket.show', $ticket)
                ->with('success', $validated['action'] === 'confirm' ? 'Ticket has been confirmed as completed.' : 'Ticket has been returned to in progress status.');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Unauthorized')) {
                abort(403);
            }

            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function reply(Request $request, Ticket $ticket, TicketService $service)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'photo' => 'nullable|image|max:5120', // 5MB max
        ]);

        try {
            $service->reply(auth()->user(), $ticket, $validated['message'], $request->file('photo'));

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
            $baseQuery->where('ticket_number', 'like', '%'.$request->ticket_number.'%');
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
            ->whereHas('unitProses', function ($query) {
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

    public function destroy(Ticket $ticket, TicketService $service)
    {
        try {
            $service->deleteUserTicket(auth()->user(), $ticket);

            return redirect()->route('user.dashboard')
                ->with('success', 'Ticket has been deleted successfully.');
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Unauthorized')) {
                abort(403);
            }
            if (str_contains($e->getMessage(), 'Only open')) {
                return redirect()->route('user.ticket.show', $ticket)->with('error', $e->getMessage());
            }
            \Log::error('Ticket deletion error: '.$e->getMessage());

            return back()->withErrors(['error' => 'Failed to delete ticket: '.$e->getMessage()]);
        }
    }
}

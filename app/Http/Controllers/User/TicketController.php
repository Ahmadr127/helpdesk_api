<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Ticket\ConfirmTicketRequest;
use App\Http\Requests\Api\Ticket\ReplyTicketRequest;
use App\Http\Requests\Api\Ticket\StoreTicketRequest;
use App\Http\Requests\Api\Ticket\UpdateTicketRequest;
use App\Http\Requests\Api\Ticket\UpdateTicketStatusRequest;
use App\Models\Building;
use App\Models\Category;
use App\Models\Location;
use App\Models\Ticket;
use App\Services\Api\TicketService;
use App\Services\DepartmentService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(TicketService $service)
    {
        $tickets = $service->allForUser(auth()->user());

        // Get categories from both SIRS and IPSRS units
        $categories = Category::where('status', 1)
            ->whereHas('unitProses', function ($query) {
                $query->whereIn('code', ['SIRS', 'IPSRS']);
            })
            ->get();

        return view('user.ticket.index', compact('tickets', 'categories'));
    }

    public function create(DepartmentService $departments)
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

        // Get the authenticated user's department — robust lookup (code, name, case-insensitive, department_id)
        $user = auth()->user();
        $deptValue = $user->department;
        $userDepartment = $departments->resolveForUser($user, true);

        if (! $userDepartment) {
            return redirect()->route('user.settings')
                ->with('error', 'Departemen "'.e($deptValue).'" tidak ditemukan di master data. Silakan perbarui departemen Anda di pengaturan profil atau hubungi administrator.');
        }

        return view('user.ticket.create', compact('categories', 'buildings', 'locations', 'userDepartment'));
    }

    public function store(StoreTicketRequest $request, TicketService $service)
    {
        // Check if user has a department
        if (! auth()->user()->department) {
            return redirect()->route('user.settings')
                ->with('error', 'Mohon lengkapi data departemen Anda terlebih dahulu di pengaturan profil untuk dapat membuat tiket.');
        }

        $validated = $request->validated();

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

    public function edit(Ticket $ticket, DepartmentService $departments)
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

        $userDepartment = $departments->resolveForUser(auth()->user());

        return view('user.ticket.edit', compact('ticket', 'categories', 'locations', 'userDepartment', 'buildings'));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket, TicketService $service)
    {
        $validated = $request->validated();

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

    public function updateStatus(UpdateTicketStatusRequest $request, Ticket $ticket)
    {
        $validated = $request->validated();

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

    public function confirm(ConfirmTicketRequest $request, Ticket $ticket, TicketService $service)
    {
        $validated = $request->validated();

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

    public function reply(ReplyTicketRequest $request, Ticket $ticket, TicketService $service)
    {
        $validated = $request->validated();

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

    public function filterByStatus($status, Request $request, TicketService $service)
    {
        $filters = $request->only(['ticket_number', 'category', 'priority', 'department', 'start_date', 'end_date']);
        $filters['status'] = $status;

        // Get the filtered tickets with pagination + statistics
        $tickets = $service->listForUser(auth()->user(), $filters, 10);
        $stats = $service->userStatistics(auth()->user());

        // Get categories only from SIRS unit
        $categories = Category::where('status', 1)
            ->whereHas('unitProses', function ($query) {
                $query->where('code', 'SIRS');
            })
            ->pluck('name');

        // Get unique departments and set priorities
        $departments = Ticket::distinct()->pluck('department');
        $priorities = ['low', 'medium', 'high'];

        return view('user.ticket.filtered', array_merge($stats, compact(
            'tickets',
            'status',
            'categories',
            'departments',
            'priorities'
        )));
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

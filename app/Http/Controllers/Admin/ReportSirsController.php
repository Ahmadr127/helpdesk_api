<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Category;
use Carbon\Carbon;
use App\Exports\ReportSirsExport;
use Illuminate\Support\Facades\Log;

class ReportSirsController extends Controller
{
    public function index(Request $request)
    {
        // Base query for all tickets (not just confirmed ones)
        $query = Ticket::query()
            ->with('user');

        // Filter by date range if provided
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by year if provided
        if ($request->filled('year')) {
            $query->whereRaw('EXTRACT(YEAR FROM created_at) = ?', [$request->year]);
        }
        
        // Filter by category if provided
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Get all tickets for statistics (not paginated)
        $allTickets = clone $query;
        $allTicketsCollection = $allTickets->get();

        // Paginate tickets for the table
        $tickets = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Get list of years for the filter dropdown using PostgreSQL's EXTRACT function
        $years = Ticket::selectRaw('DISTINCT EXTRACT(YEAR FROM created_at) as year')
            ->orderBy('year', 'desc')
            ->pluck('year');
            
        // Get categories for filter dropdown
        $categories = Category::where('status', true)->orderBy('name')->get();

        // Calculate statistics
        // Count total tickets
        $totalTickets = $allTicketsCollection->count();
        Log::info("Total tickets found: " . $totalTickets);

        // Count tickets completed in under 60 minutes
        $ticketsUnder60Minutes = 0;
        foreach ($allTicketsCollection as $ticket) {
            // Debug each ticket's status and timestamps
            Log::info("Processing ticket #{$ticket->id}:");
            Log::info("- Status: " . $ticket->status);
            Log::info("- Created At: " . ($ticket->created_at ? $ticket->created_at->format('Y-m-d H:i:s') : 'null'));
            Log::info("- Closed At: " . ($ticket->closed_at ? $ticket->closed_at->format('Y-m-d H:i:s') : 'null'));

            // Check if the ticket is closed/confirmed and has valid timestamps
            if (($ticket->status === 'closed' || $ticket->status === 'confirmed') && 
                $ticket->created_at && 
                $ticket->closed_at) {
                
                // Calculate total time from creation to closure in minutes
                $totalTime = $ticket->created_at->diffInMinutes($ticket->closed_at);
                Log::info("- Total Time from creation to closure: {$totalTime} minutes");

                // Count if total time is less than or equal to 60 minutes
                if ($totalTime <= 60) {
                    $ticketsUnder60Minutes++;
                    Log::info("- Counted as under 60 minutes: Yes");
                } else {
                    Log::info("- Counted as under 60 minutes: No (> 60 minutes)");
                }
            } else {
                Log::info("- Not counted: Invalid status or missing timestamps");
            }
        }

        Log::info("Final count of tickets under 60 minutes: " . $ticketsUnder60Minutes);

        // Calculate performance percentage based on completed tickets only
        $completedTickets = $allTicketsCollection->filter(function($ticket) {
            return ($ticket->status === 'closed' || $ticket->status === 'confirmed');
        })->count();

        $performancePercentage = $completedTickets > 0 
            ? round(($ticketsUnder60Minutes / $completedTickets) * 100, 2) 
            : 0;

        // Calculate average completion time in minutes
        $avgCompletionTime = 0;
        $closedTickets = $allTicketsCollection->filter(function($ticket) {
            return ($ticket->status === 'closed' || $ticket->status === 'confirmed') && 
                   $ticket->created_at && 
                   $ticket->closed_at;
        });
        
        if ($closedTickets->count() > 0) {
            $totalTime = $closedTickets->sum(function($ticket) {
                return $ticket->created_at->diffInMinutes($ticket->closed_at);
            });
            $avgCompletionTime = round($totalTime / $closedTickets->count());
        }

        return view('admin.report-sirs.index', compact(
            'tickets', 
            'years', 
            'categories',
            'totalTickets', 
            'ticketsUnder60Minutes', 
            'performancePercentage',
            'avgCompletionTime'
        ));
    }

    public function export(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $year = $request->input('year');
        $categoryId = $request->input('category_id');
        $selectedIds = $request->input('selected_tickets', []);

        $export = new ReportSirsExport(
            $dateFrom,
            $dateTo,
            $year,
            $selectedIds,
            $categoryId
        );

        $fileName = 'report_sirs_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        return $export->download($fileName);
    }
} 
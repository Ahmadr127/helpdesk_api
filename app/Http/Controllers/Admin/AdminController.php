<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $timeFilter = $request->input('time', 'all');
        $statusFilter = $request->input('status', 'all');

        // Base query with time filter
        $baseQuery = Ticket::query();
        $this->applyTimeFilter($baseQuery, $timeFilter);
        
        // Get total tickets based on filters
        $totalActiveTickets = (clone $baseQuery)
            ->whereIn('status', ['open', 'in_progress', 'pending'])
            ->count();
        
        $completedTickets = (clone $baseQuery)
            ->whereIn('status', ['closed', 'confirmed'])
            ->count();
            
        $totalTickets = $totalActiveTickets + $completedTickets;
        
        // Calculate remaining tickets (those not completed)
        $remainingTickets = $totalActiveTickets;

        // Calculate processing percentage
        $processingPercentage = $totalTickets > 0 
            ? round(($completedTickets / $totalTickets) * 100, 1)
            : 0;

        // Calculate ticket progress
        $ticketProgress = $totalTickets > 0
            ? round(($completedTickets / $totalTickets) * 100, 1)
            : 0;

        // Get recent tickets with relationships
        $recentTickets = Ticket::with(['user', 'location', 'category'])
            ->where('status', 'open')
            ->latest()
            ->take(4)
            ->get();

        // Get recent users
        $recentUsers = User::latest()
            ->take(5)
            ->get();

        // Priority distribution
        $priorityDistribution = [
            'low' => (clone $baseQuery)->where('priority', 'low')->count(),
            'medium' => (clone $baseQuery)->where('priority', 'medium')->count(),
            'high' => (clone $baseQuery)->where('priority', 'high')->count(),
            'urgent' => (clone $baseQuery)->where('priority', 'urgent')->count(),
        ];

        $data = [
            'totalTickets' => $totalTickets,
            'totalActiveTickets' => $totalActiveTickets,
            'remainingTickets' => $remainingTickets,
            'completedTickets' => $completedTickets,
            'processingPercentage' => $processingPercentage,
            'ticketProgress' => $ticketProgress,
            'recentTickets' => $recentTickets,
            'recentUsers' => $recentUsers,
            'priorityDistribution' => $priorityDistribution,
            // Stats for the top cards
            'openTickets' => (clone $baseQuery)->where('status', 'open')->count(),
            'inProgressTickets' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'closedAndConfirmedTickets' => (clone $baseQuery)->whereIn('status', ['closed', 'confirmed'])->count(),
        ];

        return view('admin.dashboard.index', $data);
    }

    private function applyTimeFilter($query, $timeFilter)
    {
        switch ($timeFilter) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            // 'all' doesn't need any filter
        }
        return $query;
    }

    public function getStats(Request $request)
    {
        $query = Ticket::query();
        $timeFilter = $request->input('time', 'all');
        $statusFilter = $request->input('status', 'all');

        // Create base query for time filtering
        $baseTimeQuery = function($q) use ($timeFilter) {
            $this->applyTimeFilter($q, $timeFilter);
        };

        // Create base query for status filtering
        $baseStatusQuery = function($q) use ($statusFilter) {
            if ($statusFilter !== 'all') {
                if ($statusFilter === 'closed') {
                    $q->where('status', 'closed')
                      ->where('user_confirmation', false);
                } elseif ($statusFilter === 'confirmed') {
                    $q->where('status', 'confirmed');
                } else {
                    $q->where('status', $statusFilter);
                }
            }
        };

        // Apply filters to main query
        $baseTimeQuery($query);
        $baseStatusQuery($query);

        // Get counts per date
        $ticketCounts = $query->clone()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Get status distribution with both filters
        $statusDistribution = [
            'open' => Ticket::query()
                ->tap($baseTimeQuery)
                ->tap($baseStatusQuery)
                ->where('status', 'open')
                ->count(),
            'in_progress' => Ticket::query()
                ->tap($baseTimeQuery)
                ->tap($baseStatusQuery)
                ->where('status', 'in_progress')
                ->count(),
            'confirmed' => Ticket::query()
                ->tap($baseTimeQuery)
                ->tap($baseStatusQuery)
                ->where('status', 'confirmed')
                ->count(),
            'closed' => Ticket::query()
                ->tap($baseTimeQuery)
                ->tap($baseStatusQuery)
                ->where('status', 'closed')
                ->where('user_confirmation', false)
                ->count()
        ];

        // Generate dates array based on time filter
        switch ($timeFilter) {
            case 'today':
                $dates = [today()->format('Y-m-d')];
                break;
            case 'week':
                $dates = collect(range(0, 6))->map(fn($day) => now()->startOfWeek()->addDays($day)->format('Y-m-d'));
                break;
            case 'month':
                $dates = collect(range(1, now()->daysInMonth))
                    ->map(fn($day) => now()->startOfMonth()->addDays($day - 1)->format('Y-m-d'));
                break;
            default:
                // For 'all' time, get all unique dates from the database
                $dates = Ticket::selectRaw('DISTINCT DATE(created_at) as date')
                    ->orderBy('date')
                    ->pluck('date')
                    ->toArray();
        }

        // Fill in missing dates with 0
        $counts = collect($dates)->map(fn($date) => $ticketCounts[$date] ?? 0)->toArray();

        // Get updated summary data
        $baseQuery = Ticket::query();
        $this->applyTimeFilter($baseQuery, $timeFilter);
        
        $totalActiveTickets = (clone $baseQuery)->whereIn('status', ['open', 'in_progress', 'pending'])->count();
        $completedTickets = (clone $baseQuery)->whereIn('status', ['closed', 'confirmed'])->count();
        $totalTickets = $totalActiveTickets + $completedTickets;
        $remainingTickets = $totalActiveTickets;
        $processingPercentage = $totalTickets > 0 ? round(($completedTickets / $totalTickets) * 100, 1) : 0;
        $ticketProgress = $totalTickets > 0 ? round(($completedTickets / $totalTickets) * 100, 1) : 0;

        return response()->json([
            'dates' => $dates,
            'counts' => $counts,
            'statusDistribution' => $statusDistribution,
            'summary' => [
                'totalTickets' => $totalTickets,
                'totalActiveTickets' => $totalActiveTickets,
                'remainingTickets' => $remainingTickets,
                'completedTickets' => $completedTickets,
                'processingPercentage' => $processingPercentage,
                'ticketProgress' => $ticketProgress,
            ]
        ]);
    }

    public function users()
    {
        return view('admin.users.index');
    }

    public function tickets()
    {
        return view('admin.tickets.index');
    }

    public function reports()
    {
        return view('admin.reports.index');
    }
} 
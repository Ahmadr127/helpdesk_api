<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Feedback;

class UserController extends Controller
{
    public function dashboard()
    {
        // Get ticket statistics for current user
        $userId = auth()->id();
        
        // Get total tickets from all statuses
        $totalTickets = Ticket::where('user_id', $userId)
            ->whereIn('status', ['open', 'in_progress', 'closed', 'confirmed'])
            ->count();
        
        $openTickets = Ticket::where('user_id', $userId)
            ->where('status', 'open')
            ->count();
        $inProgressTickets = Ticket::where('user_id', $userId)
            ->where('status', 'in_progress')
            ->count();
        $closedTickets = Ticket::where('user_id', $userId)
            ->where('status', 'closed')
            ->count();
        $confirmedTickets = Ticket::where('user_id', $userId)
            ->where('status', 'confirmed')
            ->count();

        // Get recent tickets
        $recentTickets = Ticket::where('user_id', $userId)
            ->select('id', 'ticket_number', 'description', 'status', 'priority', 'created_at')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        // Get order perbaikan statistics
        $totalOrders = \App\Models\OrderPerbaikan::where('created_by', $userId)->count();
        $inProgressOrders = \App\Models\OrderPerbaikan::where('created_by', $userId)
            ->where('status', 'in_progress')
            ->count();
        $confirmedOrders = \App\Models\OrderPerbaikan::where('created_by', $userId)
            ->where('status', 'confirmed')
            ->count();
        $rejectedOrders = \App\Models\OrderPerbaikan::where('created_by', $userId)
            ->where('status', 'rejected')
            ->count();
        
        // Get recent orders
        $recentOrders = \App\Models\OrderPerbaikan::where('created_by', $userId)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        $userFeedback = Feedback::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user.dashboard.index', compact(
            'totalTickets',
            'openTickets',
            'inProgressTickets',
            'closedTickets',
            'confirmedTickets',
            'recentTickets',
            'userFeedback',
            'totalOrders',
            'inProgressOrders',
            'confirmedOrders',
            'rejectedOrders',
            'recentOrders'
        ));
    }
} 
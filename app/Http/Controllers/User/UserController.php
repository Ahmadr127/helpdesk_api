<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Pengajuan;

class UserController extends Controller
{
    public function dashboard()
    {
        $userId = auth()->id();

        $itQuery = Pengajuan::sirs()->where('pemohon_id', $userId);

        $totalTickets = (clone $itQuery)->whereIn('status', ['open', 'in_progress', 'ditutup', 'confirmed'])->count();
        $openTickets = (clone $itQuery)->where('status', 'open')->count();
        $inProgressTickets = (clone $itQuery)->where('status', 'in_progress')->count();
        $confirmedTickets = (clone $itQuery)->where('status', 'confirmed')->count();
        $ditutupTickets = (clone $itQuery)->where('status', 'ditutup')->count();

        $recentTickets = (clone $itQuery)
            ->with(['kategori.jenisList'])
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        $ipsrsQuery = Pengajuan::umum()->where('pemohon_id', $userId);

        $totalOrders = (clone $ipsrsQuery)->count();
        $inProgressOrders = (clone $ipsrsQuery)->where('status', 'in_progress')->count();
        $confirmedOrders = (clone $ipsrsQuery)->where('status', 'confirmed')->count();
        $rejectedOrders = (clone $ipsrsQuery)->where('status', 'rejected')->count();

        $recentOrders = (clone $ipsrsQuery)
            ->with(['kategori.jenisList'])
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        $userFeedback = Feedback::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user.dashboard.index', compact(
            'totalTickets',
            'openTickets',
            'inProgressTickets',
            'ditutupTickets',
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

<?php

namespace App\Http\Controllers\AdministrasiUmum;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderBarang;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $orders = OrderBarang::with('user')
            ->latest()
            ->paginate(10);

        return view('administrasi-umum.order-perbaikan.index', compact('orders'));
    }

    public function show(OrderBarang $order)
    {
        return view('administrasi-umum.order-perbaikan.show', compact('order'));
    }

    public function updateStatus(Request $request, OrderBarang $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,rejected',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $order->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'],
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Order status has been updated successfully.');
    }

    public function export(Request $request)
    {
        $orders = OrderBarang::with('user')
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->date_from, function($query, $date) {
                return $query->whereDate('created_at', '>=', $date);
            })
            ->when($request->date_to, function($query, $date) {
                return $query->whereDate('created_at', '<=', $date);
            })
            ->get();

        // Add export logic here (e.g., Excel or PDF generation)
        
        return back()->with('success', 'Export started. You will receive the file shortly.');
    }
} 
<?php

namespace App\Http\Controllers\Api;

use App\Models\KategoriOrder;
use App\Models\OrderPerbaikan;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends BaseApiController
{
    public function userDashboard(Request $request)
    {
        $user = $request->user();
        $tickets = Ticket::where('user_id', $user->id);
        $orders = OrderPerbaikan::where('created_by', $user->id);

        return $this->success([
            'tickets' => [
                'total' => (clone $tickets)->count(),
                'open' => (clone $tickets)->where('status', 'open')->count(),
                'in_progress' => (clone $tickets)->where('status', 'in_progress')->count(),
                'closed' => (clone $tickets)->where('status', 'closed')->count(),
                'confirmed' => (clone $tickets)->where('status', 'confirmed')->count(),
            ],
            'orders' => [
                'total' => (clone $orders)->count(),
                'open' => (clone $orders)->where('status', 'open')->count(),
                'in_progress' => (clone $orders)->where('status', 'in_progress')->count(),
                'tutup' => (clone $orders)->where('status', 'tutup')->count(),
                'confirmed' => (clone $orders)->where('status', 'confirmed')->count(),
                'rejected' => (clone $orders)->where('status', 'rejected')->count(),
            ],
            'user' => $user,
        ], 'User dashboard');
    }

    public function adminDashboard(Request $request)
    {
        return $this->success([
            'users' => [
                'total' => User::count(),
                'active' => User::where('status', 1)->count(),
                'inactive' => User::where('status', 0)->count(),
                'admin' => User::adminIT()->count(),
            ],
            'tickets' => [
                'total' => Ticket::count(),
                'open' => Ticket::where('status', 'open')->count(),
                'in_progress' => Ticket::where('status', 'in_progress')->count(),
                'closed' => Ticket::where('status', 'closed')->count(),
                'confirmed' => Ticket::where('status', 'confirmed')->count(),
                'by_priority' => [
                    'high' => Ticket::where('priority', 'high')->count(),
                    'medium' => Ticket::where('priority', 'medium')->count(),
                    'low' => Ticket::where('priority', 'low')->count(),
                ],
            ],
            'orders' => [
                'total' => OrderPerbaikan::count(),
                'open' => OrderPerbaikan::where('status', 'open')->count(),
                'in_progress' => OrderPerbaikan::where('status', 'in_progress')->count(),
                'tutup' => OrderPerbaikan::where('status', 'tutup')->count(),
                'confirmed' => OrderPerbaikan::where('status', 'confirmed')->count(),
                'rejected' => OrderPerbaikan::where('status', 'rejected')->count(),
            ],
        ], 'Admin dashboard');
    }

public function administrasiDashboard(Request $request)
    {
        $user = $request->user();
        $base = OrderPerbaikan::query();

        // PJ scope
        $names = KategoriOrder::where('status', 1)
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->pluck('name');
        if ($names->isNotEmpty()) {
            $base->whereIn('kategori_order', $names->all());
        } else {
            $base->whereNotExists(function ($sub) {
                $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('kategori_order as ko')
                    ->join('kategori_order_user as kou', 'kou.kategori_order_id', '=', 'ko.id')
                    ->whereColumn('ko.name', 'order_perbaikan.kategori_order')
                    ->where('ko.status', 1);
            });
        }

        return $this->success([
            'orders' => [
                'total' => (clone $base)->count(),
                'open' => (clone $base)->where('status', 'open')->count(),
                'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
                'tutup' => (clone $base)->where('status', 'tutup')->count(),
                'confirmed' => (clone $base)->where('status', 'confirmed')->count(),
                'rejected' => (clone $base)->where('status', 'rejected')->count(),
                'rendah' => (clone $base)->where('prioritas', 'RENDAH')->count(),
                'sedang' => (clone $base)->where('prioritas', 'SEDANG')->count(),
                'tinggi' => (clone $base)->where('prioritas', 'TINGGI/URGENT')->count(),
            ],
        ], 'Administrasi dashboard');
    }
}

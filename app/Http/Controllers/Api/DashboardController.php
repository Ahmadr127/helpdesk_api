<?php

namespace App\Http\Controllers\Api;

use App\Models\OrderPerbaikan;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends BaseApiController
{
    public function userDashboard(Request $request)
    {
        $user = $request->user();
        $tickets = Ticket::where('user_id',$user->id);
        $orders = OrderPerbaikan::where('created_by',$user->id);
        return $this->success([
            'tickets'=>[
                'total'=> (clone $tickets)->count(),
                'open'=> (clone $tickets)->where('status','open')->count(),
                'in_progress'=> (clone $tickets)->where('status','in_progress')->count(),
                'closed'=> (clone $tickets)->where('status','closed')->count(),
                'confirmed'=> (clone $tickets)->where('status','confirmed')->count(),
            ],
            'orders'=>[
                'total'=> (clone $orders)->count(),
                'open'=> (clone $orders)->where('status','open')->count(),
                'in_progress'=> (clone $orders)->where('status','in_progress')->count(),
                'confirmed'=> (clone $orders)->where('status','confirmed')->count(),
                'rejected'=> (clone $orders)->where('status','rejected')->count(),
            ],
            'user'=> $user
        ], 'User dashboard');
    }

    public function adminDashboard(Request $request)
    {
        return $this->success([
            'users'=>[
                'total'=> User::count(),
                'active'=> User::where('status',1)->count(),
                'inactive'=> User::where('status',0)->count(),
                'admin'=> User::where('role','admin')->count(),
            ],
            'tickets'=>[
                'total'=> Ticket::count(),
                'open'=> Ticket::where('status','open')->count(),
                'in_progress'=> Ticket::where('status','in_progress')->count(),
                'closed'=> Ticket::where('status','closed')->count(),
                'confirmed'=> Ticket::where('status','confirmed')->count(),
                'by_priority'=>[
                    'high'=> Ticket::where('priority','high')->count(),
                    'medium'=> Ticket::where('priority','medium')->count(),
                    'low'=> Ticket::where('priority','low')->count(),
                ]
            ],
            'orders'=>[
                'total'=> OrderPerbaikan::count(),
                'open'=> OrderPerbaikan::where('status','open')->count(),
                'in_progress'=> OrderPerbaikan::where('status','in_progress')->count(),
                'confirmed'=> OrderPerbaikan::where('status','confirmed')->count(),
                'rejected'=> OrderPerbaikan::where('status','rejected')->count(),
            ]
        ], 'Admin dashboard');
    }

    public function administrasiDashboard(Request $request)
    {
        return $this->success([
            'orders'=>[
                'total'=> OrderPerbaikan::count(),
                'open'=> OrderPerbaikan::where('status','open')->count(),
                'in_progress'=> OrderPerbaikan::where('status','in_progress')->count(),
                'confirmed'=> OrderPerbaikan::where('status','confirmed')->count(),
                'rejected'=> OrderPerbaikan::where('status','rejected')->count(),
                'rendah'=> OrderPerbaikan::where('prioritas','RENDAH')->count(),
                'sedang'=> OrderPerbaikan::where('prioritas','SEDANG')->count(),
                'tinggi'=> OrderPerbaikan::where('prioritas','TINGGI/URGENT')->count(),
            ]
        ], 'Administrasi dashboard');
    }
}

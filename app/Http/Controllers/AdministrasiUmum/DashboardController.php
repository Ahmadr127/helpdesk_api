<?php

namespace App\Http\Controllers\AdministrasiUmum;

use App\Http\Controllers\Controller;
use App\Services\Pengajuan\PengajuanDashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(protected PengajuanDashboardService $dashboard) {}

    private function viewNamespace(Request $request): string
    {
        return 'admin.ipsrs-dashboard';
    }

    public function index(Request $request)
    {
        $timeFilter = $request->input('time_filter', 'all');
        $statusFilter = $request->input('status_filter', 'all');

        $dashboardData = $this->dashboard->ipsrsDashboardData($timeFilter, $statusFilter, false, auth()->user());

        return view($this->viewNamespace($request).'.index', $dashboardData);
    }

    public function stats(Request $request)
    {
        $timeFilter = $request->input('time_filter', 'all');
        $statusFilter = $request->input('status_filter', 'all');

        $dashboardData = $this->dashboard->ipsrsDashboardData($timeFilter, $statusFilter, true, auth()->user());

        $dashboardData['recentOrdersHtml'] = view($this->viewNamespace($request).'.partials.recent-orders', [
            'recentOrders' => $dashboardData['recentOrders'],
        ])->render();

        return response()->json($dashboardData);
    }

    public function getStats(Request $request)
    {
        $timeFilter = $request->input('time', 'all');
        $statusFilter = $request->input('status', 'all');

        $data = $this->dashboard->ipsrsDashboardData($timeFilter, $statusFilter, true, auth()->user());

        return response()->json([
            'dates' => collect($data['orderTrends'])->pluck('date')->toArray(),
            'counts' => collect($data['orderTrends'])->pluck('count')->toArray(),
            'statusDistribution' => $data['statusDistribution'],
        ]);
    }
}

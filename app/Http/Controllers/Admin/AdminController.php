<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Pengajuan\PengajuanDashboardService;
use App\Services\Pengajuan\PengajuanScopeService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(protected PengajuanDashboardService $dashboard, protected PengajuanScopeService $scope) {}

    public function dashboard(Request $request)
    {
        $timeFilter = $request->input('time', 'all');
        $statusFilter = $request->input('status', 'all');

        $data = $this->dashboard->itDashboardData($timeFilter, $statusFilter, auth()->user());

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
        $timeFilter = $request->input('time', 'all');
        $statusFilter = $request->input('status', 'all');

        $query = $this->scope->visibleTo(auth()->user())
            ->whereHas('kategori.jenis', fn ($q) => $q->where('code', 'IT'));
        $this->applyTimeFilter($query, $timeFilter);

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $countsByDate = (clone $query)
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM-DD') as date, COUNT(*) as count")
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $statusDistribution = [
            'open' => (clone $query)->where('status', 'open')->count(),
            'in_progress' => (clone $query)->where('status', 'in_progress')->count(),
            'confirmed' => (clone $query)->where('status', 'confirmed')->count(),
            'ditutup' => (clone $query)->where('status', 'ditutup')->count(),
        ];

        $dates = array_keys($countsByDate);
        $counts = array_values($countsByDate);

        $summary = $this->dashboard->itDashboardData($timeFilter, $statusFilter, auth()->user());

        return response()->json([
            'dates' => $dates,
            'counts' => $counts,
            'statusDistribution' => $statusDistribution,
            'summary' => $summary,
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

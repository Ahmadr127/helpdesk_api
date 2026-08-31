<?php

namespace App\Http\Controllers\AdministrasiUmum;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderPerbaikan;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    private const CACHE_TTL = 30; // 30 seconds cache

    public function index(Request $request)
    {
            $timeFilter = $request->input('time_filter', 'all');
            $statusFilter = $request->input('status_filter', 'all');

        // Get all dashboard data
        $dashboardData = $this->getDashboardData($timeFilter, $statusFilter);

        return view('administrasi-umum.dashboard.index', $dashboardData);
    }

    public function stats(Request $request)
    {
        $timeFilter = $request->input('time_filter', 'all');
        $statusFilter = $request->input('status_filter', 'all');

        // Get fresh dashboard data (bypass cache for real-time updates)
        $dashboardData = $this->getDashboardData($timeFilter, $statusFilter, true);

        // Add HTML for recent orders
        $dashboardData['recentOrdersHtml'] = view('administrasi-umum.dashboard.partials.recent-orders', [
            'recentOrders' => $dashboardData['recentOrders']
        ])->render();

        return response()->json($dashboardData);
    }

    private function getDashboardData($timeFilter, $statusFilter, $fresh = false)
    {
        $cacheKey = "dashboard_data_{$timeFilter}_{$statusFilter}";

        if (!$fresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Get base query
            $query = OrderPerbaikan::query();

            // Apply time filter
            switch ($timeFilter) {
                case 'today':
                $query->whereDate('created_at', Carbon::today());
                    break;
                case 'week':
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'month':
                $query->whereMonth('created_at', Carbon::now()->month)
                      ->whereYear('created_at', Carbon::now()->year);
                    break;
            }

            // Apply status filter if not 'all'
            if ($statusFilter !== 'all') {
                $query->where('status', $statusFilter);
            }

        // Get all required statistics in a single query
        $statistics = DB::table('order_perbaikan')
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected_orders,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as confirmed_orders,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as open_orders
            ', [
                OrderPerbaikan::STATUS_IN_PROGRESS,
                OrderPerbaikan::STATUS_REJECTED,
                OrderPerbaikan::STATUS_CONFIRMED,
                OrderPerbaikan::STATUS_OPEN
            ])
            ->when($timeFilter === 'today', function ($query) {
                return $query->whereDate('created_at', Carbon::today());
            })
            ->when($timeFilter === 'week', function ($query) {
                return $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            })
            ->when($timeFilter === 'month', function ($query) {
                return $query->whereMonth('created_at', Carbon::now()->month)
                            ->whereYear('created_at', Carbon::now()->year);
            })
            ->when($statusFilter !== 'all', function ($query) use ($statusFilter) {
                return $query->where('status', $statusFilter);
            })
            ->first();

        // Calculate percentages
        $totalOrdersForProgress = $statistics->open_orders + $statistics->pending_orders;
        $progressPercentage = $totalOrdersForProgress > 0 
            ? round(($statistics->pending_orders / $totalOrdersForProgress) * 100, 1) 
            : 0;

        $totalCompletedPercentage = $statistics->total_orders > 0 
            ? round(($statistics->confirmed_orders / $statistics->total_orders) * 100, 1) 
            : 0;

        // Get current year and target year
        $currentYear = Carbon::now()->year;
        $targetYear = Carbon::now()->addYear()->year;

        // Get order trends
        $orderTrends = $this->getOrderTrends($timeFilter, $statusFilter);

        // Get status distribution
        $statusDistribution = [
            'open' => $statistics->open_orders,
            'in_progress' => $statistics->pending_orders,
            'confirmed' => $statistics->confirmed_orders,
            'rejected' => $statistics->rejected_orders
        ];

        // Get recent orders with eager loading
        $recentOrders = OrderPerbaikan::with(['creator', 'location'])
            ->whereIn('status', ['open', 'in_progress'])
            ->latest()
            ->take(8)
            ->get();

        // Calculate remaining orders
        $remainingOrders = $statistics->total_orders - $statistics->confirmed_orders;

        $data = [
            'timeFilter' => $timeFilter,
            'statusFilter' => $statusFilter,
            'totalOrders' => $statistics->total_orders,
            'pendingOrders' => $statistics->pending_orders,
            'rejectedOrders' => $statistics->rejected_orders,
            'confirmedOrders' => $statistics->confirmed_orders,
            'openOrders' => $statistics->open_orders,
            'orderTrends' => $orderTrends,
            'statusDistribution' => $statusDistribution,
            'recentOrders' => $recentOrders,
            'progressPercentage' => $progressPercentage,
            'totalCompletedPercentage' => $totalCompletedPercentage,
            'currentYear' => $currentYear,
            'targetYear' => $targetYear,
            'remainingOrders' => $remainingOrders
        ];

        if (!$fresh) {
            Cache::put($cacheKey, $data, self::CACHE_TTL);
        }

        return $data;
    }

    private function getOrderTrends($timeFilter, $statusFilter)
    {
        $cacheKey = "order_trends_{$timeFilter}_{$statusFilter}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($timeFilter, $statusFilter) {
        // Base query
        $query = OrderPerbaikan::query();

        // Apply status filter if not 'all'
        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        // Set time range and group format based on filter
        switch ($timeFilter) {
            case 'today':
                $startDate = Carbon::today();
                $endDate = Carbon::today()->endOfDay();
                    $groupFormat = 'HH24:00';
                $formatType = 'hour';
                break;
            case 'week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                    $groupFormat = 'YYYY-MM-DD';
                $formatType = 'day';
                break;
            case 'month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                    $groupFormat = 'YYYY-MM-DD';
                $formatType = 'day';
                break;
                default:
                    $startDate = Carbon::now()->subMonths(2);
                $endDate = Carbon::now();
                    $groupFormat = 'YYYY-MM-DD';
                $formatType = 'day';
                break;
        }

            // Apply date range and get results
            $results = $query->whereBetween('created_at', [$startDate, $endDate])
                ->select(
            DB::raw("TO_CHAR(created_at, '{$groupFormat}') as date"),
            DB::raw('COUNT(*) as count')
        )
                ->groupBy('date')
        ->orderBy('date', 'asc')
                ->get();

            // Fill in missing dates if necessary
            if ($timeFilter !== 'today' && $timeFilter !== 'all' && !$results->isEmpty()) {
            $results = $this->fillMissingDates($results, $startDate, $endDate, $formatType);
        }

        return $results;
        });
    }

    private function fillMissingDates($results, $startDate, $endDate, $formatType)
    {
        $filledResults = collect([]);
        $current = $startDate->copy();
        $format = $formatType === 'day' ? 'Y-m-d' : 'H:00';
        $existingDates = $results->pluck('count', 'date')->toArray();

        while ($current <= $endDate) {
            $dateKey = $current->format($format);
            
                $filledResults->push([
                    'date' => $dateKey,
                'count' => $existingDates[$dateKey] ?? 0
            ]);

            if ($formatType === 'day') {
                $current->addDay();
            } else {
                $current->addHour();
            }
        }

        return $filledResults;
    }

    public function getStats(Request $request)
    {
        $query = OrderPerbaikan::query();
        $timeFilter = $request->input('time', 'all');
        $statusFilter = $request->input('status', 'all');

        // Time filter
        switch ($timeFilter) {
            case 'today':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'week':
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('created_at', Carbon::now()->month)
                      ->whereYear('created_at', Carbon::now()->year);
                break;
        }

        // Status filter
        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        // Get counts per date - using PostgreSQL date formatting
        $orderCounts = $query->clone()
            ->select(DB::raw("TO_CHAR(created_at, 'YYYY-MM-DD') as date"), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Get status distribution
        $statusDistribution = [
            'open' => $query->clone()->where('status', OrderPerbaikan::STATUS_OPEN)->count(),
            'in_progress' => $query->clone()->where('status', OrderPerbaikan::STATUS_IN_PROGRESS)->count(),
            'confirmed' => $query->clone()->where('status', OrderPerbaikan::STATUS_CONFIRMED)->count(),
            'rejected' => $query->clone()->where('status', OrderPerbaikan::STATUS_REJECTED)->count()
        ];

        // Generate dates array
        $dates = [];
        if ($timeFilter === 'today') {
            $dates = [Carbon::today()->format('Y-m-d')];
        } elseif ($timeFilter === 'week') {
            for ($i = 0; $i <= 6; $i++) {
                $dates[] = Carbon::now()->startOfWeek()->addDays($i)->format('Y-m-d');
            }
        } elseif ($timeFilter === 'month') {
            $daysInMonth = Carbon::now()->daysInMonth;
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $dates[] = Carbon::now()->startOfMonth()->addDays($i - 1)->format('Y-m-d');
            }
        } else {
            // For 'all', we use the actual dates in the database
            $datesFromDb = OrderPerbaikan::selectRaw("TO_CHAR(created_at, 'YYYY-MM-DD') as date")
                ->distinct()
                ->orderBy('date')
                ->pluck('date')
                ->toArray();
            $dates = $datesFromDb;
        }

        // Fill in missing dates with 0
        $counts = [];
        foreach ($dates as $date) {
            $counts[] = $orderCounts[$date] ?? 0;
        }

        return response()->json([
            'dates' => $dates,
            'counts' => $counts,
            'statusDistribution' => $statusDistribution,
        ]);
    }

    public function profile()
    {
        return view('administrasi-umum.profile.index', [
            'user' => Auth::user()
        ]);
    }

    public function settings()
    {
        return view('administrasi-umum.settings.index', [
            'user' => Auth::user()
        ]);
    }
} 
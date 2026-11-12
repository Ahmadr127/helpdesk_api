<?php

namespace App\Services\Pengajuan;

use App\Models\Pengajuan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PengajuanDashboardService
{
    public function __construct(protected PengajuanScopeService $scopeService) {}

    /**
     * Query dasar pengajuan. Flavour yang diterima: 'sirs' atau 'umum'.
     * Di luar keduanya, query tidak difilter apa pun.
     */
    public function baseQuery(?string $flavour = null): Builder
    {
        $query = Pengajuan::query();

        if ($flavour === 'sirs') {
            $query->sirs();
        } elseif ($flavour === 'umum') {
            $query->umum();
        }

        return $query;
    }

    /**
     * Ringkasan dashboard untuk mobile — SELURUH logika dihitung di server.
     *
     * Kontrak:
     * - `own`   = pengajuan milik user yang sedang login (scope list user).
     * - `admin` = pengajuan yang terlihat oleh admin sesuai scope list admin
     *             (role-kategori + milik sendiri), TANPA filter jenis lain
     *             supaya angkanya sama persis dengan jumlah baris di list.
     * - Section admin hanya diisi bila user memang boleh membuka list itu
     *   (permission dashboard + permission fiturnya), sehingga Flutter tidak
     *   perlu deciding sendiri.
     *
     * Definisi status (penting agar konsisten antar kartu):
     * - `active`   = open + in_progress (sedang berjalan)
     * - `pending`  = ditutup (menunggu konfirmasi user)
     * - `done`     = confirmed + rejected (sudah selesai)
     */
    public function mobileSummary(User $user): array
    {
        return [
            'own' => [
                'tickets' => $this->countByStatus($this->ownQuery($user, 'sirs')),
                'orders' => $this->countByStatus($this->ownQuery($user, 'umum')),
            ],
            'admin' => [
                'tickets' => $this->canSeeAdminTickets($user)
                    ? $this->countByStatus($this->adminQuery($user, 'sirs'))
                    : null,
                'orders' => $this->canSeeAdminOrders($user)
                    ? $this->countByStatus($this->adminQuery($user, 'umum'))
                    : null,
            ],
        ];
    }

    /** Scope "milik sendiri" — sama dengan endpoint list user. */
    protected function ownQuery(User $user, string $flavour): Builder
    {
        $query = Pengajuan::query()->where('pemohon_id', $user->id);

        return $flavour === 'sirs' ? $query->sirs() : $query->umum();
    }

    /** Scope admin — sama dengan endpoint list admin (role-kategori + milik sendiri). */
    protected function adminQuery(User $user, string $flavour): Builder
    {
        $query = $this->scopeService->visibleTo($user);

        return $flavour === 'sirs' ? $query->sirs() : $query->umum();
    }

    protected function canSeeAdminTickets(User $user): bool
    {
        return $user->hasPermission('dashboard.it') && $user->hasPermission('ticket');
    }

    protected function canSeeAdminOrders(User $user): bool
    {
        return $user->hasPermission('dashboard.ipsrs') && $user->hasPermission('order');
    }

    /**
     * Hitung jumlah per status + turunan (active/pending/done) dalam satu query.
     *
     * @return array{total:int,open:int,in_progress:int,ditutup:int,confirmed:int,rejected:int,active:int,pending:int,done:int}
     */
    protected function countByStatus(Builder $query): array
    {
        $row = (clone $query)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as c_open,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as c_in_progress,
                SUM(CASE WHEN status = 'ditutup' THEN 1 ELSE 0 END) as c_ditutup,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as c_confirmed,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as c_rejected
            ")
            ->first();

        $open = (int) ($row->c_open ?? 0);
        $inProgress = (int) ($row->c_in_progress ?? 0);
        $ditutup = (int) ($row->c_ditutup ?? 0);
        $confirmed = (int) ($row->c_confirmed ?? 0);
        $rejected = (int) ($row->c_rejected ?? 0);

        return [
            'total' => (int) ($row->total ?? 0),
            'open' => $open,
            'in_progress' => $inProgress,
            'ditutup' => $ditutup,
            'confirmed' => $confirmed,
            'rejected' => $rejected,
            'active' => $open + $inProgress,
            'pending' => $ditutup,
            'done' => $confirmed + $rejected,
        ];
    }

    public function applyTimeFilter(Builder $query, string $timeFilter): Builder
    {
        return match ($timeFilter) {
            'today' => $query->whereDate('created_at', Carbon::today()),
            'week' => $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]),
            'month' => $query->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year),
            default => $query,
        };
    }

    public function userStats(User $user): array
    {
        $tickets = $this->baseQuery('sirs')->where('pemohon_id', $user->id);
        $orders = $this->baseQuery('umum')->where('pemohon_id', $user->id);

        return [
            'tickets' => [
                'total' => (clone $tickets)->count(),
                'open' => (clone $tickets)->where('status', 'open')->count(),
                'in_progress' => (clone $tickets)->where('status', 'in_progress')->count(),
                'ditutup' => (clone $tickets)->where('status', 'ditutup')->count(),
                'confirmed' => (clone $tickets)->where('status', 'confirmed')->count(),
            ],
            'orders' => [
                'total' => (clone $orders)->count(),
                'open' => (clone $orders)->where('status', 'open')->count(),
                'in_progress' => (clone $orders)->where('status', 'in_progress')->count(),
                'ditutup' => (clone $orders)->where('status', 'ditutup')->count(),
                'confirmed' => (clone $orders)->where('status', 'confirmed')->count(),
                'rejected' => (clone $orders)->where('status', 'rejected')->count(),
            ],
            'user' => $user,
        ];
    }

    public function adminStats(?User $user = null): array
    {
        $tickets = $this->baseQuery('sirs');
        $orders = $this->baseQuery('umum');

        $categoryIds = $this->categoryIdsForUser($user);
        if (is_array($categoryIds)) {
            if ($categoryIds === []) {
                $tickets->whereRaw('1 = 0');
                $orders->whereRaw('1 = 0');
            } else {
                $tickets->whereIn('kategori_id', $categoryIds);
                $orders->whereIn('kategori_id', $categoryIds);
            }
        }

        return [
            'users' => [
                'total' => \App\Models\User::count(),
                'active' => \App\Models\User::where('status', 1)->count(),
                'inactive' => \App\Models\User::where('status', 0)->count(),
                'admin' => \App\Models\User::where('role', 'admin')->count(),
            ],
            'tickets' => [
                'total' => $tickets->count(),
                'open' => (clone $tickets)->where('status', 'open')->count(),
                'in_progress' => (clone $tickets)->where('status', 'in_progress')->count(),
                'ditutup' => (clone $tickets)->where('status', 'ditutup')->count(),
                'confirmed' => (clone $tickets)->where('status', 'confirmed')->count(),
                'by_priority' => [
                    'high' => (clone $tickets)->where('prioritas', 'high')->count(),
                    'medium' => (clone $tickets)->where('prioritas', 'medium')->count(),
                    'low' => (clone $tickets)->where('prioritas', 'low')->count(),
                ],
            ],
            'orders' => [
                'total' => $orders->count(),
                'open' => (clone $orders)->where('status', 'open')->count(),
                'in_progress' => (clone $orders)->where('status', 'in_progress')->count(),
                'ditutup' => (clone $orders)->where('status', 'ditutup')->count(),
                'confirmed' => (clone $orders)->where('status', 'confirmed')->count(),
                'rejected' => (clone $orders)->where('status', 'rejected')->count(),
            ],
        ];
    }

    public function administrasiStats(?User $user = null): array
    {
        $orders = $this->baseQuery('umum');

        $categoryIds = $this->categoryIdsForUser($user);
        if (is_array($categoryIds)) {
            if ($categoryIds === []) {
                $orders->whereRaw('1 = 0');
            } else {
                $orders->whereIn('kategori_id', $categoryIds);
            }
        }

        return [
            'orders' => [
                'total' => $orders->count(),
                'open' => (clone $orders)->where('status', 'open')->count(),
                'in_progress' => (clone $orders)->where('status', 'in_progress')->count(),
                'ditutup' => (clone $orders)->where('status', 'ditutup')->count(),
                'confirmed' => (clone $orders)->where('status', 'confirmed')->count(),
                'rejected' => (clone $orders)->where('status', 'rejected')->count(),
                'rendah' => (clone $orders)->where('prioritas', 'low')->count(),
                'sedang' => (clone $orders)->where('prioritas', 'medium')->count(),
                'tinggi' => (clone $orders)->where('prioritas', 'high')->count(),
            ],
        ];
    }

    public function itDashboardData(string $timeFilter = 'all', string $statusFilter = 'all', ?User $user = null): array
    {
        $baseQuery = $this->baseQuery('sirs');
        $this->applyCategoryFilter($baseQuery, $user);
        $this->applyTimeFilter($baseQuery, $timeFilter);

        if ($statusFilter !== 'all') {
            $baseQuery->where('status', $statusFilter);
        }

        $totalActiveTickets = (clone $baseQuery)->whereIn('status', ['open', 'in_progress', 'ditutup'])->count();
        $completedTickets = (clone $baseQuery)->whereIn('status', ['confirmed', 'rejected'])->count();
        $totalTickets = $totalActiveTickets + $completedTickets;
        $remainingTickets = $totalActiveTickets;
        $processingPercentage = $totalTickets > 0 ? round(($completedTickets / $totalTickets) * 100, 1) : 0;
        $ticketProgress = $processingPercentage;

        $recentBase = $this->baseQuery('sirs');
        $this->applyCategoryFilter($recentBase, $user);

        $recentTickets = (clone $recentBase)
            ->where('status', 'open')
            ->latest()
            ->take(4)
            ->get();

        $priorityDistribution = [
            'low' => (clone $baseQuery)->where('prioritas', 'low')->count(),
            'medium' => (clone $baseQuery)->where('prioritas', 'medium')->count(),
            'high' => (clone $baseQuery)->where('prioritas', 'high')->count(),
            'urgent' => 0,
        ];

        return [
            'totalTickets' => $totalTickets,
            'totalActiveTickets' => $totalActiveTickets,
            'remainingTickets' => $remainingTickets,
            'completedTickets' => $completedTickets,
            'processingPercentage' => $processingPercentage,
            'ticketProgress' => $ticketProgress,
            'recentTickets' => $recentTickets,
            'recentUsers' => \App\Models\User::latest()->take(5)->get(),
            'priorityDistribution' => $priorityDistribution,
            'openTickets' => (clone $baseQuery)->where('status', 'open')->count(),
            'inProgressTickets' => (clone $baseQuery)->where('status', 'in_progress')->count(),
            'closedAndConfirmedTickets' => (clone $baseQuery)->whereIn('status', ['ditutup', 'confirmed', 'rejected'])->count(),
        ];
    }

    public function ipsrsDashboardData(string $timeFilter = 'all', string $statusFilter = 'all', bool $fresh = false, ?User $user = null): array
    {
        $cacheKey = "pengajuan_ipsrs_dashboard_{$timeFilter}_{$statusFilter}_".($user?->id ?? 'all');

        if (! $fresh && \Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return \Illuminate\Support\Facades\Cache::get($cacheKey);
        }

        $baseQuery = $this->baseQuery('umum');
        $this->applyTimeFilter($baseQuery, $timeFilter);

        if ($statusFilter !== 'all') {
            $baseQuery->where('status', $statusFilter);
        }

        $statistics = DB::table('pengajuan')
            ->join('kategori_pengajuan', 'kategori_pengajuan.id', '=', 'pengajuan.kategori_id')
            ->whereExists(fn ($sub) => $sub
                ->select(DB::raw(1))
                ->from('kategori_jenis')
                ->join('jenis_pengajuan', 'jenis_pengajuan.id', '=', 'kategori_jenis.jenis_id')
                ->whereColumn('kategori_jenis.kategori_id', '=', 'kategori_pengajuan.id')
                ->where('jenis_pengajuan.code', '!=', 'IT'))
            ->when(true, function ($q) use ($user) {
                $categoryIds = $this->categoryIdsForUser($user);
                if (is_array($categoryIds)) {
                    if ($categoryIds === []) {
                        $q->whereRaw('1 = 0');
                    } else {
                        $q->whereIn('pengajuan.kategori_id', $categoryIds);
                    }
                }
            })
            ->selectRaw("
                COUNT(*) as total_orders,
                SUM(CASE WHEN pengajuan.status = 'in_progress' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN pengajuan.status = 'rejected' THEN 1 ELSE 0 END) as rejected_orders,
                SUM(CASE WHEN pengajuan.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                SUM(CASE WHEN pengajuan.status = 'open' THEN 1 ELSE 0 END) as open_orders
            ")
            ->when($timeFilter === 'today', fn ($q) => $q->whereDate('pengajuan.created_at', Carbon::today()))
            ->when($timeFilter === 'week', fn ($q) => $q->whereBetween('pengajuan.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]))
            ->when($timeFilter === 'month', fn ($q) => $q->whereMonth('pengajuan.created_at', Carbon::now()->month)->whereYear('pengajuan.created_at', Carbon::now()->year))
            ->when($statusFilter !== 'all', fn ($q) => $q->where('pengajuan.status', $statusFilter))
            ->first();

        $totalOrdersForProgress = (int) $statistics->open_orders + (int) $statistics->pending_orders;
        $progressPercentage = $totalOrdersForProgress > 0
            ? round(((int) $statistics->pending_orders / $totalOrdersForProgress) * 100, 1)
            : 0;

        $totalCompletedPercentage = (int) $statistics->total_orders > 0
            ? round(((int) $statistics->confirmed_orders / (int) $statistics->total_orders) * 100, 1)
            : 0;

        $recentBase = $this->baseQuery('umum');
        $this->applyCategoryFilter($recentBase, $user);

        $recentOrders = (clone $recentBase)
            ->whereIn('status', ['open', 'in_progress'])
            ->with(['pemohon', 'lokasi', 'kategori', 'lampirans'])
            ->latest()
            ->take(8)
            ->get();

        $data = [
            'timeFilter' => $timeFilter,
            'statusFilter' => $statusFilter,
            'totalOrders' => (int) $statistics->total_orders,
            'pendingOrders' => (int) $statistics->pending_orders,
            'rejectedOrders' => (int) $statistics->rejected_orders,
            'confirmedOrders' => (int) $statistics->confirmed_orders,
            'openOrders' => (int) $statistics->open_orders,
            'orderTrends' => $this->ipsrsOrderTrends($timeFilter, $statusFilter),
            'statusDistribution' => [
                'open' => (int) $statistics->open_orders,
                'in_progress' => (int) $statistics->pending_orders,
                'confirmed' => (int) $statistics->confirmed_orders,
                'rejected' => (int) $statistics->rejected_orders,
            ],
            'recentOrders' => $recentOrders,
            'progressPercentage' => $progressPercentage,
            'totalCompletedPercentage' => $totalCompletedPercentage,
            'currentYear' => Carbon::now()->year,
            'targetYear' => Carbon::now()->addYear()->year,
            'remainingOrders' => (int) $statistics->total_orders - (int) $statistics->confirmed_orders,
        ];

        if (! $fresh) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, $data, 30);
        }

        return $data;
    }

    protected function ipsrsOrderTrends(string $timeFilter, string $statusFilter)
    {
        $query = $this->baseQuery('umum');

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        [$startDate, $endDate, $groupFormat, $formatType] = match ($timeFilter) {
            'today' => [Carbon::today(), Carbon::today()->endOfDay(), 'HH24:00', 'hour'],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek(), 'YYYY-MM-DD', 'day'],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(), 'YYYY-MM-DD', 'day'],
            default => [Carbon::now()->subMonths(2), Carbon::now(), 'YYYY-MM-DD', 'day'],
        };

        $results = $query->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw("TO_CHAR(created_at, '{$groupFormat}') as date"), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return $results;
    }

    protected function categoryIdsForUser(?User $user): ?array
    {
        if (! $user || $user->hasPermission('pengajuan.view_all')) {
            return null;
        }

        $role = \App\Models\Role::where('slug', $user->role)->first();

        if (! $role) {
            return [];
        }

        return $role->kategoris()->pluck('kategori_pengajuan.id')->map(fn ($id) => (int) $id)->all();
    }

    protected function applyCategoryFilter(Builder $query, ?User $user): void
    {
        $categoryIds = $this->categoryIdsForUser($user);

        if (is_array($categoryIds)) {
            if ($categoryIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('kategori_id', $categoryIds);
            }
        }
    }
}

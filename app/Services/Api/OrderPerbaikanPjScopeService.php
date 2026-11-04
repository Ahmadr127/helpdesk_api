<?php

namespace App\Services\Api;

use App\Models\KategoriOrder;
use App\Models\OrderPerbaikan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderPerbaikanPjScopeService extends OrderPerbaikanService
{
    /**
     * Nama kategori order yang di-assign ke user sebagai PJ.
     */
    public function assignedCategoryNames(User $user): Collection
    {
        return KategoriOrder::where('status', 1)
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->pluck('name');
    }

    /**
     * Scope query order berdasarkan PJ kategori user.
     * - Punya assignment → hanya kategori tersebut.
     * - Tidak punya → order yang kategorinya tidak punya PJ (fallback Admin Umum).
     */
    protected function applyPjScope($query, User $user): void
    {
        $names = $this->assignedCategoryNames($user);

        if ($names->isNotEmpty()) {
            $query->whereIn('kategori_order', $names->all());
        } else {
            $query->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('kategori_order as ko')
                    ->join('kategori_order_user as kou', 'kou.kategori_order_id', '=', 'ko.id')
                    ->whereColumn('ko.name', 'order_perbaikan.kategori_order')
                    ->where('ko.status', 1);
            });
        }
    }

    public function listForAdmin(array $filters = [], int $perPage = 15, ?User $viewer = null)
    {
        $viewer = $viewer ?? auth()->user() ?? request()->user();
        $query = OrderPerbaikan::with(['creator', 'history', 'location', 'penanggungJawab', 'department', 'department.location', 'department.building']);

        // Re-apply filters from parent (replicate listForAdmin filter logic)
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('nama_peminta', 'like', "%{$search}%")
                    ->orWhere('kategori_order', 'like', "%{$search}%");
            });
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('tanggal', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('tanggal', '<=', $filters['date_to']);
        }

        $status = $filters['status'] ?? null;
        if (is_array($status)) {
            $query->whereIn('status', $status);
        } elseif (! empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        if (! empty($filters['prioritas'])) {
            $query->where('prioritas', $filters['prioritas']);
        }
        if (! empty($filters['location_id'])) {
            $query->where('lokasi', $filters['location_id']);
        }
        if (! empty($filters['kategori_order'])) {
            $query->where('kategori_order', $filters['kategori_order']);
        }

        if ($viewer) {
            $this->applyPjScope($query, $viewer);
        }

        return $query->latest()->paginate($perPage);
    }

    public function statistics(?User $viewer = null): array
    {
        $viewer = $viewer ?? auth()->user() ?? request()->user();

        $base = OrderPerbaikan::query();
        if ($viewer) {
            $names = $this->assignedCategoryNames($viewer);
            if ($names->isNotEmpty()) {
                $base->whereIn('kategori_order', $names->all());
            } else {
                $base->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('kategori_order as ko')
                        ->join('kategori_order_user as kou', 'kou.kategori_order_id', '=', 'ko.id')
                        ->whereColumn('ko.name', 'order_perbaikan.kategori_order')
                        ->where('ko.status', 1);
                });
            }
        }

        return [
            'totalOrders' => (clone $base)->count(),
            'openOrders' => (clone $base)->where('status', 'open')->count(),
            'inProgressOrders' => (clone $base)->where('status', 'in_progress')->count(),
            'tutupOrders' => (clone $base)->where('status', 'tutup')->count(),
            'confirmedOrders' => (clone $base)->where('status', 'confirmed')->count(),
            'rejectedOrders' => (clone $base)->where('status', 'rejected')->count(),
            'rendahOrders' => (clone $base)->where('prioritas', 'RENDAH')->count(),
            'sedangOrders' => (clone $base)->where('prioritas', 'SEDANG')->count(),
            'tinggiOrders' => (clone $base)->where('prioritas', 'TINGGI/URGENT')->count(),
        ];
    }
}

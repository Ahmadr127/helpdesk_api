<?php

namespace App\Services\Api;

use App\Models\Location;
use App\Models\OrderPerbaikan;
use App\Models\UnitProses;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderPerbaikanService
{
    public function listForUser(User $user, array $filters = [], int $perPage = 15)
    {
        $query = OrderPerbaikan::with(['creator','history','location'])->where('created_by', $user->id);

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search){
                $q->where('nomor','like',"%{$search}%")
                  ->orWhere('nama_barang','like',"%{$search}%")
                  ->orWhere('keluhan','like',"%{$search}%")
                  ->orWhere('kode_inventaris','like',"%{$search}%");
            });
        }
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('tanggal', [$filters['start_date'].' 00:00:00', $filters['end_date'].' 23:59:59']);
        }
        if (!empty($filters['prioritas'])) {
            $query->where('prioritas', $filters['prioritas']);
        }

        return $query->orderBy('created_at','desc')->paginate($perPage);
    }

    public function listForAdmin(array $filters = [], int $perPage = 15)
    {
        $query = OrderPerbaikan::with(['creator','history','location']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search){
                $q->where('nomor','like',"%{$search}%")
                  ->orWhere('nama_barang','like',"%{$search}%")
                  ->orWhere('nama_peminta','like',"%{$search}%");
            });
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('tanggal','>=',$filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('tanggal','<=',$filters['date_to']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            // default open+in_progress if no status
            // for API we return all unless filtered? Keep as original: open/in_progress default when no filter
            // But allow 'all' to bypass
            if (($filters['status'] ?? null) !== 'all') {
                // keep default? comment out to return all for API flexibility
                // $query->whereIn('status', ['open','in_progress']);
            }
        }
        if (!empty($filters['prioritas'])) {
            $query->where('prioritas', $filters['prioritas']);
        }
        if (!empty($filters['location_id'])) {
            $query->where('lokasi', $filters['location_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function statistics(): array
    {
        return [
            'totalOrders' => OrderPerbaikan::count(),
            'openOrders' => OrderPerbaikan::where('status','open')->count(),
            'inProgressOrders' => OrderPerbaikan::where('status','in_progress')->count(),
            'confirmedOrders' => OrderPerbaikan::where('status','confirmed')->count(),
            'rejectedOrders' => OrderPerbaikan::where('status','rejected')->count(),
            'rendahOrders' => OrderPerbaikan::where('prioritas','RENDAH')->count(),
            'sedangOrders' => OrderPerbaikan::where('prioritas','SEDANG')->count(),
            'tinggiOrders' => OrderPerbaikan::where('prioritas','TINGGI/URGENT')->count(),
        ];
    }

    public function userStatistics(User $user): array
    {
        return [
            'total' => OrderPerbaikan::where('created_by',$user->id)->count(),
            'open' => OrderPerbaikan::where('created_by',$user->id)->where('status','open')->count(),
            'in_progress' => OrderPerbaikan::where('created_by',$user->id)->where('status','in_progress')->count(),
            'confirmed' => OrderPerbaikan::where('created_by',$user->id)->where('status','confirmed')->count(),
            'rejected' => OrderPerbaikan::where('created_by',$user->id)->where('status','rejected')->count(),
        ];
    }

    public function create(User $user, array $validated, $fotoFile = null): OrderPerbaikan
    {
        return DB::transaction(function() use ($user, $validated, $fotoFile){
            $today = now();
            $prefix = 'OP/RTG/MTC-' . $today->format('Ymd');
            $lastOrder = OrderPerbaikan::withTrashed()->where('nomor','like',$prefix.'%')->orderBy('nomor','desc')->first();
            if ($lastOrder) {
                $lastNumber = (int) substr($lastOrder->nomor, -3);
                $newNumber = str_pad($lastNumber+1, 3, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '001';
            }
            $nomor = $prefix . $newNumber;

            $unitProses = UnitProses::where('code', $validated['unit_proses_code'])->firstOrFail();

            $fotoPath = null;
            if ($fotoFile) {
                $filename = 'order_' . time() . '_' . $fotoFile->getClientOriginalName();
                $fotoPath = $fotoFile->storeAs('order-photos', $filename, 'public');
            }

            $order = OrderPerbaikan::create([
                'nomor' => $nomor,
                'tanggal' => $validated['tanggal'],
                'unit_proses' => $unitProses->code,
                'unit_proses_name' => $unitProses->name,
                'unit_penerima' => 'MTC',
                'nip_peminta' => $user->nip ?? null,
                'nama_peminta' => $user->name,
                'jenis_barang' => $validated['jenis_barang'],
                'kode_inventaris' => $validated['kode_inventaris'],
                'nama_barang' => $validated['nama_barang'],
                'lokasi' => $validated['lokasi'],
                'keluhan' => $validated['keluhan'],
                'prioritas' => $validated['prioritas'],
                'foto' => $fotoPath,
                'status' => 'open',
                'created_by' => $user->id,
            ]);

            $order->history()->create([
                'status' => 'open',
                'keterangan' => 'Order dibuat via API',
                'created_by' => $user->id,
            ]);

            return $order->load(['creator','history','location']);
        });
    }

    public function update(User $user, OrderPerbaikan $order, array $validated, $fotoFile = null): OrderPerbaikan
    {
        if ($order->created_by !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }
        if ($order->status !== 'open') {
            throw new \Exception('Hanya order dengan status open yang dapat diedit.', 422);
        }

        return DB::transaction(function() use ($user, $order, $validated, $fotoFile){
            if ($fotoFile) {
                if ($order->foto) {
                    Storage::disk('public')->delete($order->foto);
                }
                $filename = 'order_' . time() . '_' . $fotoFile->getClientOriginalName();
                $fotoPath = $fotoFile->storeAs('order-photos', $filename, 'public');
                $validated['foto'] = $fotoPath;
            }

            $order->update([
                'jenis_barang' => $validated['jenis_barang'],
                'kode_inventaris' => $validated['kode_inventaris'],
                'nama_barang' => $validated['nama_barang'],
                'lokasi' => $validated['lokasi'],
                'keluhan' => $validated['keluhan'],
                'prioritas' => $validated['prioritas'],
                'foto' => $validated['foto'] ?? $order->foto,
                'updated_by' => $user->id,
            ]);

            $order->history()->create([
                'status' => $order->status,
                'keterangan' => 'Order diperbarui via API',
                'created_by' => $user->id,
            ]);

            return $order->fresh()->load(['creator','history','location']);
        });
    }

    public function delete(User $user, OrderPerbaikan $order): void
    {
        if ($order->created_by !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }
        if ($order->status !== 'open') {
            throw new \Exception('Hanya order dengan status open yang dapat dihapus.', 422);
        }

        DB::transaction(function() use ($order){
            $order->history()->delete();
            if ($order->foto) {
                Storage::disk('public')->delete($order->foto);
            }
            $order->forceDelete();
        });
    }

    public function updateStatus(User $admin, OrderPerbaikan $order, array $data): OrderPerbaikan
    {
        return DB::transaction(function() use ($admin, $order, $data){
            if ($order->status === 'open' && $data['status'] === 'in_progress') {
                if (empty($data['nama_penanggung_jawab'])) {
                    throw new \Exception('nama_penanggung_jawab required when moving open -> in_progress', 422);
                }
                $updateData = [
                    'status' => $data['status'],
                    'nama_penanggung_jawab' => $data['nama_penanggung_jawab'],
                    'follow_up' => $data['follow_up'],
                    'updated_by' => $admin->id,
                ];
                if (!empty($data['prioritas'])) {
                    $updateData['prioritas'] = $data['prioritas'];
                }
                $order->update($updateData);
            } else {
                $updateData = [
                    'status' => $data['status'],
                    'follow_up' => $data['follow_up'],
                    'updated_by' => $admin->id,
                ];
                if (!empty($data['prioritas'])) {
                    $updateData['prioritas'] = $data['prioritas'];
                }
                $order->update($updateData);
            }

            $order->history()->create([
                'status' => $data['status'],
                'keterangan' => $data['follow_up'] . (!empty($data['prioritas']) ? " (Prioritas diubah menjadi {$data['prioritas']})" : ""),
                'created_by' => $admin->id,
            ]);

            return $order->fresh()->load(['creator','history','location']);
        });
    }

    public function confirm(User $admin, OrderPerbaikan $order): OrderPerbaikan
    {
        return DB::transaction(function() use ($admin, $order){
            $order->update([
                'status' => OrderPerbaikan::STATUS_CONFIRMED,
                'nama_penanggung_jawab' => $admin->name,
                'updated_by' => $admin->id
            ]);
            $order->history()->create([
                'status' => OrderPerbaikan::STATUS_CONFIRMED,
                'keterangan' => 'Order dikonfirmasi via API',
                'created_by' => $admin->id
            ]);
            return $order->fresh();
        });
    }

    public function reject(User $admin, OrderPerbaikan $order): OrderPerbaikan
    {
        return DB::transaction(function() use ($admin, $order){
            $order->update([
                'status' => OrderPerbaikan::STATUS_REJECTED,
                'nama_penanggung_jawab' => $admin->name,
                'updated_by' => $admin->id
            ]);
            $order->history()->create([
                'status' => OrderPerbaikan::STATUS_REJECTED,
                'keterangan' => 'Order ditolak via API',
                'created_by' => $admin->id
            ]);
            return $order->fresh();
        });
    }

    public function start(User $admin, OrderPerbaikan $order): OrderPerbaikan
    {
        return DB::transaction(function() use ($admin, $order){
            $order->update([
                'status' => 'in_progress',
                'nama_penanggung_jawab' => $admin->name,
                'updated_by' => $admin->id,
            ]);
            $order->history()->create([
                'status' => 'in_progress',
                'keterangan' => 'Pengerjaan order dimulai via API',
                'created_by' => $admin->id
            ]);
            // notify creator if needed
            return $order->fresh();
        });
    }
}

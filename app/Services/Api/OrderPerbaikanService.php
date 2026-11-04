<?php

namespace App\Services\Api;

use App\Models\Department;
use App\Models\KategoriOrder;
use App\Models\OrderPerbaikan;

use App\Models\User;
use App\Support\Notifications\Notify;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderPerbaikanService
{
    public function listForUser(User $user, array $filters = [], int $perPage = 15, bool $forceOwner = false)
    {
        $canViewAll = ! $forceOwner
            && ($user->isAdminUmum() || $user->isAdminIT());

        if ($canViewAll) {
            $query = OrderPerbaikan::with(['creator', 'history', 'location', 'department', 'department.location', 'department.building', 'penanggungJawab']);
        } else {
            $query = OrderPerbaikan::with(['creator', 'history', 'location', 'department', 'department.location', 'department.building', 'penanggungJawab'])->where('created_by', $user->id);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                    ->orWhere('keluhan', 'like', "%{$search}%")
                    ->orWhere('kategori_order', 'like', "%{$search}%");
            });
        }
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('tanggal', [$filters['start_date'].' 00:00:00', $filters['end_date'].' 23:59:59']);
        }
        if (! empty($filters['prioritas'])) {
            $query->where('prioritas', $filters['prioritas']);
        }
        if (! empty($filters['kategori_order'])) {
            $query->where('kategori_order', $filters['kategori_order']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function listForAdmin(array $filters = [], int $perPage = 15, ?User $viewer = null)
    {
        $viewer ??= auth()->user();
        $query = OrderPerbaikan::with(['creator', 'history', 'location', 'penanggungJawab', 'department', 'department.location', 'department.building']);

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

        return $query->latest()->paginate($perPage);
    }

    public function statistics(): array
    {
        return [
            'totalOrders' => OrderPerbaikan::count(),
            'openOrders' => OrderPerbaikan::where('status', 'open')->count(),
            'inProgressOrders' => OrderPerbaikan::where('status', 'in_progress')->count(),
            'tutupOrders' => OrderPerbaikan::where('status', 'tutup')->count(),
            'confirmedOrders' => OrderPerbaikan::where('status', 'confirmed')->count(),
            'rejectedOrders' => OrderPerbaikan::where('status', 'rejected')->count(),
            'rendahOrders' => OrderPerbaikan::where('prioritas', 'RENDAH')->count(),
            'sedangOrders' => OrderPerbaikan::where('prioritas', 'SEDANG')->count(),
            'tinggiOrders' => OrderPerbaikan::where('prioritas', 'TINGGI/URGENT')->count(),
        ];
    }

    /**
     * Default PJ untuk sebuah kategori order: user yang ditugaskan pada
     * kategori tsb. Assignment unit/departemen diabaikan.
     */
    public static function defaultPjIdsFor(?string $kategori): array
    {
        $name = trim((string) $kategori);
        if ($name === '') {
            return [];
        }

        $kategoriOrder = KategoriOrder::where('name', $name)->where('status', 1)->first();
        if (! $kategoriOrder) {
            return [];
        }

        return $kategoriOrder->users()->where('users.status', 1)->pluck('users.id')->all();
    }

    public function userStatistics(User $user): array
    {
        return [
            'total' => OrderPerbaikan::where('created_by', $user->id)->count(),
            'open' => OrderPerbaikan::where('created_by', $user->id)->where('status', 'open')->count(),
            'in_progress' => OrderPerbaikan::where('created_by', $user->id)->where('status', 'in_progress')->count(),
            'tutup' => OrderPerbaikan::where('created_by', $user->id)->where('status', 'tutup')->count(),
            'confirmed' => OrderPerbaikan::where('created_by', $user->id)->where('status', 'confirmed')->count(),
            'rejected' => OrderPerbaikan::where('created_by', $user->id)->where('status', 'rejected')->count(),
        ];
    }

    /**
     * Nomor order berurutan per hari: OP/RTG/MTC-YYYYMMDD###.
     */
    public function nextNumber(): string
    {
        $prefix = 'OP/RTG/MTC-'.now()->format('Ymd');

        $lastOrder = OrderPerbaikan::withTrashed()
            ->where('nomor', 'like', $prefix.'%')
            ->orderBy('nomor', 'desc')
            ->first();

        $sequence = $lastOrder
            ? (int) substr($lastOrder->nomor, -3) + 1
            : 1;

        return $prefix.str_pad($sequence, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Simpan lampiran order ke disk public, kembalikan path relatif.
     */
    public function storeLampiran($file): string
    {
        $filename = 'lampiran_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();

        return $file->storeAs('order-lampiran', $filename, 'public');
    }

    /**
     * Simpan banyak lampiran, kembalikan array path.
     */
    public function storeLampirans(array $files): array
    {
        $paths = [];
        foreach ($files as $file) {
            if ($file) {
                $paths[] = $this->storeLampiran($file);
            }
        }
        return array_values(array_filter($paths));
    }

    /**
     * Nama file aman (tanpa spasi/karakter khusus) agar URL storage valid.
     */
    private function safeUploadName(string $originalName): string
    {
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION) ?: 'jpg');
        $base = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $base) ?: 'foto';

        return $base.'.'.$ext;
    }

    public function create(User $user, array $validated, $fotoFiles = null, string $source = 'API'): OrderPerbaikan
    {
        // Normalisasi ke array
        if ($fotoFiles instanceof \Illuminate\Http\UploadedFile) {
            $fotoFiles = [$fotoFiles];
        } elseif (! is_array($fotoFiles)) {
            $fotoFiles = $fotoFiles ? [$fotoFiles] : [];
        }
        // Batasi maks 3
        $fotoFiles = array_slice(array_values(array_filter($fotoFiles)), 0, 3);

        $order = DB::transaction(function () use ($user, $validated, $fotoFiles) {
            $nomor = $this->nextNumber();

            $departmentId = $validated['department_id'] ?? null;
            if ($departmentId && ! Department::whereKey($departmentId)->exists()) {
                $departmentId = null;
            }
            if (! $departmentId) {
                $department = null;
                if (! empty($user->department_id)) {
                    $department = Department::find($user->department_id);
                }
                if (! $department && $user->department) {
                    $deptValue = $user->department;
                    $department = Department::where('code', $deptValue)->first()
                        ?? Department::where('name', $deptValue)->first()
                        ?? Department::whereRaw('LOWER(code) = LOWER(?)', [$deptValue])->first()
                        ?? Department::whereRaw('LOWER(name) = LOWER(?)', [$deptValue])->first();
                }
                $departmentId = $department?->id;
            }

            $fotoPaths = [];
            foreach ($fotoFiles as $fotoFile) {
                if ($fotoFile) {
                    $filename = 'order_'.time().'_'.$this->safeUploadName($fotoFile->getClientOriginalName());
                    $fotoPaths[] = $fotoFile->storeAs('order-photos', $filename, 'public');
                }
            }

            $order = OrderPerbaikan::create([
                'nomor' => $nomor,
                'tanggal' => $validated['tanggal'],
                'department_id' => $departmentId,
                'nama_peminta' => $user->name,
                'kategori_order' => $validated['kategori_order'] ?? null,
                'lokasi' => $validated['lokasi'] ?? null,
                'gedung' => $validated['gedung'] ?? null,
                'keluhan' => $validated['keluhan'],
                'prioritas' => $validated['prioritas'],
                'foto' => ! empty($fotoPaths) ? $fotoPaths : null,
                'status' => 'open',
                'created_by' => $user->id,
            ]);

            // PJ otomatis mengikuti kategori order (user yang ditugaskan).
            $pjIds = self::defaultPjIdsFor($order->kategori_order);
            if (! empty($pjIds)) {
                $order->penanggungJawab()->sync($pjIds);
            }

            $order->history()->create([
                'status' => 'open',
                'keterangan' => '',
                'created_by' => $user->id,
            ]);

            return $order->fresh()->load(['creator', 'history', 'location', 'department', 'department.location', 'department.building', 'penanggungJawab']);
        });

        // Notifikasi hanya ke PJ yang ditugaskan pada kategori order.
        // Fallback ke Admin Umum bila kategori belum punya petugas.
        $targets = $order->penanggungJawab;
        if ($targets->isEmpty()) {
            $targets = User::adminUmum()->get();
        }
        Notify::orderToUsers($order, 'order_created', $user, $targets);
        foreach ($targets as $target) {
            try {
                $target->notify(new \App\Notifications\OrderPerbaikanAssigned($order));
            } catch (\Throwable $e) {
                \Log::warning('Gagal kirim notifikasi PJ order', [
                    'order_id' => $order->id,
                    'user_id' => $target->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $order;
    }

    public function update(User $user, OrderPerbaikan $order, array $validated, $fotoFiles = null, string $source = 'API'): OrderPerbaikan
    {
        if ($order->created_by !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }
        if ($order->status !== 'open') {
            throw new \Exception('Hanya order dengan status open yang dapat diedit.', 422);
        }

        if ($fotoFiles instanceof \Illuminate\Http\UploadedFile) {
            $fotoFiles = [$fotoFiles];
        } elseif (! is_array($fotoFiles)) {
            $fotoFiles = $fotoFiles ? [$fotoFiles] : [];
        }
        $fotoFiles = array_slice(array_values(array_filter($fotoFiles)), 0, 3);

        return DB::transaction(function () use ($user, $order, $validated, $fotoFiles, $source) {
            if (! empty($fotoFiles)) {
                if (is_array($order->foto)) {
                    foreach ($order->foto as $old) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
                    }
                } elseif ($order->foto) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($order->foto);
                }
                $fotoPaths = [];
                foreach ($fotoFiles as $fotoFile) {
                    if ($fotoFile) {
                        $filename = 'order_'.time().'_'.$this->safeUploadName($fotoFile->getClientOriginalName());
                        $fotoPaths[] = $fotoFile->storeAs('order-photos', $filename, 'public');
                    }
                }
                $validated['foto'] = ! empty($fotoPaths) ? $fotoPaths : $order->foto;
            }

            $order->update([
                'kategori_order' => $validated['kategori_order'] ?? $order->kategori_order,
                'lokasi' => $validated['lokasi'] ?? $order->lokasi,
                'gedung' => $validated['gedung'] ?? $order->gedung,
                'keluhan' => $validated['keluhan'] ?? $order->keluhan,
                'prioritas' => $validated['prioritas'],
                'foto' => $validated['foto'] ?? $order->foto,
                'updated_by' => $user->id,
            ]);

            // Kategori berubah → PJ mengikuti user yang ditugaskan di kategori itu.
            if (! empty($validated['kategori_order'])) {
                $pjIds = self::defaultPjIdsFor($validated['kategori_order']);
                if (! empty($pjIds)) {
                    $order->penanggungJawab()->sync($pjIds);
                }
            }

            $order->history()->create([
                'status' => $order->status,
                'keterangan' => 'Order diperbarui via '.$source,
                'created_by' => $user->id,
            ]);

            return $order->fresh()->load(['creator', 'history', 'location', 'penanggungJawab']);
        });
    }

    public function updateAdminOrder(User $admin, OrderPerbaikan $order, array $validated, $fotoFiles = null, array $pjIds = [], string $source = 'API'): OrderPerbaikan
    {
        if ($fotoFiles instanceof \Illuminate\Http\UploadedFile) {
            $fotoFiles = [$fotoFiles];
        } elseif (! is_array($fotoFiles)) {
            $fotoFiles = $fotoFiles ? [$fotoFiles] : [];
        }
        $fotoFiles = array_slice(array_values(array_filter($fotoFiles)), 0, 3);

        return DB::transaction(function () use ($admin, $order, $validated, $fotoFiles, $pjIds, $source) {
            $oldPjNames = $order->penanggungJawab()->orderBy('name')->pluck('name')->all();
            $oldPjIds = $order->penanggungJawab()->pluck('users.id')->all();

            if (! empty($fotoFiles)) {
                if (is_array($order->foto)) {
                    foreach ($order->foto as $old) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
                    }
                } elseif ($order->foto) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($order->foto);
                }
                $fotoPaths = [];
                foreach ($fotoFiles as $fotoFile) {
                    if ($fotoFile) {
                        $filename = 'order_'.time().'_'.$this->safeUploadName($fotoFile->getClientOriginalName());
                        $fotoPaths[] = $fotoFile->storeAs('order-photos', $filename, 'public');
                    }
                }
                $validated['foto'] = ! empty($fotoPaths) ? $fotoPaths : $order->foto;
            }

            $order->update([
                'kategori_order' => $validated['kategori_order'] ?? $order->kategori_order,
                'lokasi' => $validated['lokasi'] ?? $order->lokasi,
                'gedung' => $validated['gedung'] ?? $order->gedung,
                'keluhan' => $validated['keluhan'] ?? $order->keluhan,
                'prioritas' => $validated['prioritas'],
                'foto' => $validated['foto'] ?? $order->foto,
                'updated_by' => $admin->id,
            ]);

            if (! empty($pjIds)) {
                $ids = array_values(array_unique($pjIds));
            } else {
                // Belum ada PJ eksplisit → ikuti user yang ditugaskan di kategori.
                $defaultPj = self::defaultPjIdsFor($validated['kategori_order'] ?? $order->kategori_order);
                if (! empty($defaultPj)) {
                    $ids = $defaultPj;
                } else {
                    $existing = $order->penanggungJawab()->pluck('id')->all();
                    $ids = empty($existing) ? [$admin->id] : array_values(array_unique($existing));
                }
            }
            $order->penanggungJawab()->sync($ids);

            $newPjNames = $order->penanggungJawab()->orderBy('name')->pluck('name')->all();
            $keterangan = 'Order diperbarui via '.$source.' (admin)';
            if ($oldPjNames !== $newPjNames) {
                $keterangan .= '. PJ: '.(empty($oldPjNames) ? '-' : implode(', ', $oldPjNames))
                    .' -> '.(empty($newPjNames) ? '-' : implode(', ', $newPjNames));

                // Beri tahu PJ baru setelah transaksi commit (tanpa memblokir edit).
                DB::afterCommit(function () use ($order, $admin, $ids, $oldPjIds) {
                    try {
                        $addedIds = array_values(array_diff($ids, $oldPjIds));
                        if (empty($addedIds)) {
                            return;
                        }
                        $assigned = User::whereIn('id', $addedIds)->get();
                        Notify::orderToUsers($order, 'order_assigned', $admin, $assigned);
                        foreach ($assigned as $pj) {
                            $pj->notify(new \App\Notifications\OrderPerbaikanAssigned($order));
                        }
                    } catch (\Throwable $e) {
                        \Log::warning('Gagal kirim notifikasi PJ baru order', [
                            'order_id' => $order->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                });
            }

            $order->history()->create([
                'status' => $order->status,
                'keterangan' => $keterangan,
                'created_by' => $admin->id,
            ]);

            return $order->fresh()->load(['creator', 'history', 'location', 'penanggungJawab']);
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

        DB::transaction(function () use ($order) {
            $order->history()->delete();
            if ($order->foto) {
                Storage::disk('public')->delete($order->foto);
            }
            $order->forceDelete();
        });
    }

    public function updateStatus(User $admin, OrderPerbaikan $order, array $data): OrderPerbaikan
    {
        $order = DB::transaction(function () use ($admin, $order, $data) {
            if ($order->status === 'open' && $data['status'] === 'in_progress') {
                $updateData = [
                    'status' => $data['status'],
                    'follow_up' => $data['follow_up'],
                    'updated_by' => $admin->id,
                ];
                if (! empty($data['prioritas'])) {
                    $updateData['prioritas'] = $data['prioritas'];
                }
                $order->update($updateData);
            } else {
                $updateData = [
                    'status' => $data['status'],
                    'follow_up' => $data['follow_up'],
                    'updated_by' => $admin->id,
                ];
                if (! empty($data['prioritas'])) {
                    $updateData['prioritas'] = $data['prioritas'];
                }
                if ($data['status'] === 'tutup') {
                    $updateData['closed_at'] = now();
                }
                $order->update($updateData);
            }

            $order->penanggungJawab()->syncWithoutDetaching([$admin->id]);

            $historyData = [
                'status' => $data['status'],
                'keterangan' => $data['follow_up'].(! empty($data['prioritas']) ? " (Prioritas diubah menjadi {$data['prioritas']})" : ''),
                'created_by' => $admin->id,
            ];

            if (! empty($data['lampiran'])) {
                $historyData['lampiran'] = $data['lampiran'];
            }

            $order->history()->create($historyData);

            return $order->fresh()->load(['creator', 'history', 'location', 'penanggungJawab']);
        });

        Notify::orderToUser($order, 'order_status_updated', $admin);

        // Also DB notification for inbox
        $creator = $order->creator ?? User::find($order->created_by);
        if ($creator) {
            $creator->notify(new \App\Notifications\OrderPerbaikanStatusUpdated($order));
        }

        return $order;
    }

    public function confirm(User $admin, OrderPerbaikan $order, string $source = 'API'): OrderPerbaikan
    {
        $order = DB::transaction(function () use ($admin, $order, $source) {
            $order->update([
                'status' => OrderPerbaikan::STATUS_CONFIRMED,
                'updated_by' => $admin->id,
            ]);
            $order->penanggungJawab()->syncWithoutDetaching([$admin->id]);
            $order->history()->create([
                'status' => OrderPerbaikan::STATUS_CONFIRMED,
                'keterangan' => 'Order dikonfirmasi via '.$source,
                'created_by' => $admin->id,
            ]);

            return $order->fresh()->load(['creator']);
        });

        Notify::orderToUser($order, 'order_confirmed', $admin);
        $creator = $order->creator ?? User::find($order->created_by);
        if ($creator) {
            $creator->notify(new \App\Notifications\OrderPerbaikanStatusUpdated($order));
        }

        return $order;
    }

    public function reject(User $admin, OrderPerbaikan $order, string $source = 'API'): OrderPerbaikan
    {
        $order = DB::transaction(function () use ($admin, $order, $source) {
            $order->update([
                'status' => OrderPerbaikan::STATUS_REJECTED,
                'updated_by' => $admin->id,
            ]);
            $order->penanggungJawab()->syncWithoutDetaching([$admin->id]);
            $order->history()->create([
                'status' => OrderPerbaikan::STATUS_REJECTED,
                'keterangan' => 'Order ditolak via '.$source,
                'created_by' => $admin->id,
            ]);

            return $order->fresh()->load(['creator']);
        });

        Notify::orderToUser($order, 'order_rejected', $admin);
        $creator = $order->creator ?? User::find($order->created_by);
        if ($creator) {
            $creator->notify(new \App\Notifications\OrderPerbaikanStatusUpdated($order));
        }

        return $order;
    }

    public function start(User $admin, OrderPerbaikan $order, string $source = 'API'): OrderPerbaikan
    {
        $order = DB::transaction(function () use ($admin, $order, $source) {
            $order->update([
                'status' => 'in_progress',
                'updated_by' => $admin->id,
            ]);
            $order->penanggungJawab()->syncWithoutDetaching([$admin->id]);
            $order->history()->create([
                'status' => 'in_progress',
                'keterangan' => 'Pengerjaan order dimulai via '.$source,
                'created_by' => $admin->id,
            ]);

            return $order->fresh()->load(['creator']);
        });

        Notify::orderToUser($order, 'order_started', $admin);
        $creator = $order->creator ?? User::find($order->created_by);
        if ($creator) {
            $creator->notify(new \App\Notifications\OrderPerbaikanStatusUpdated($order));
        }

        return $order;
    }

    public function userConfirm(User $user, OrderPerbaikan $order, ?string $catatan = null, bool $selesai = true, $lampiran = null): OrderPerbaikan
    {
        if (is_string($lampiran)) {
            $lampiran = $lampiran ? [$lampiran] : null;
        }
        if (is_array($lampiran)) {
            $lampiran = array_values(array_filter($lampiran));
            if (empty($lampiran)) {
                $lampiran = null;
            }
        }
        if ($order->created_by !== $user->id) {
            throw new \Exception('Unauthorized', 403);
        }
        if ($order->status !== OrderPerbaikan::STATUS_TUTUP) {
            throw new \Exception('Hanya order dengan status tutup yang dapat dikonfirmasi.', 422);
        }

        $order = DB::transaction(function () use ($user, $order, $catatan, $selesai, $lampiran) {
            $status = $selesai ? OrderPerbaikan::STATUS_CONFIRMED : OrderPerbaikan::STATUS_IN_PROGRESS;
            $keterangan = $catatan ?: ($selesai
                ? 'User mengonfirmasi order sudah selesai.'
                : 'User menyatakan order belum selesai. Order dibuka kembali.');

            $order->update([
                'status' => $status,
                'updated_by' => $user->id,
            ]);

            $historyData = [
                'status' => $status,
                'follow_up' => $catatan,
                'keterangan' => $keterangan,
                'created_by' => $user->id,
            ];

            if ($lampiran) {
                $historyData['lampiran'] = $lampiran;
            }

            $order->history()->create($historyData);

            return $order->fresh()->load(['creator', 'location']);
        });

        // Beri tahu PJ kategori order (bukan semua Admin Umum) bahwa user
        // sudah mengkonfirmasi, agar notifikasi hanya muncul ke penanggung jawab.
        $targets = $order->penanggungJawab;
        if ($targets->isEmpty()) {
            $targets = User::adminUmum()->get();
        }
        foreach ($targets as $pj) {
            $pj->notify(new \App\Notifications\OrderPerbaikanStatusUpdated($order));
        }

        return $order;
    }

/**
     * Auto-confirm order yang sudah 'tutup' lebih dari $hours jam tanpa konfirmasi user.
     * Dipanggil oleh scheduler (system actor, tanpa auth).
     */
    public function autoConfirmStale(int $hours = 24): int
    {
        $threshold = now()->subHours($hours);
        $orders = OrderPerbaikan::where('status', OrderPerbaikan::STATUS_TUTUP)
            ->where('closed_at', '<=', $threshold)
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            DB::transaction(function () use ($order) {
                $order->update([
                    'status' => OrderPerbaikan::STATUS_CONFIRMED,
                    'updated_by' => null,
                ]);
                $order->history()->create([
                    'status' => OrderPerbaikan::STATUS_CONFIRMED,
                    'keterangan' => 'Auto-confirm setelah 24 jam tanpa konfirmasi user.',
                    'created_by' => null,
                ]);
            });

            // Notifikasi ke creator + PJ kategori order (bukan semua Admin Umum).
            try {
                $creator = $order->creator ?? User::find($order->created_by);
                if ($creator) {
                    $creator->notify(new \App\Notifications\OrderPerbaikanStatusUpdated($order));
                }
                $targets = $order->penanggungJawab;
                if ($targets->isEmpty()) {
                    $targets = User::adminUmum()->get();
                }
                foreach ($targets as $adm) {
                    $adm->notify(new \App\Notifications\OrderPerbaikanStatusUpdated($order));
                }
            } catch (\Throwable $e) {
                \Log::warning('Gagal kirim notif auto-confirm order', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $count++;
        }

        return $count;
    }
}

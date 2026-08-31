<?php

namespace App\Http\Controllers\AdministrasiUmum;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderPerbaikan;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Notifications\OrderPerbaikanStatusUpdated;
use App\Exports\OrderPerbaikanExport;
use App\Models\OrderPerbaikanHistory;
use App\Models\Location;
use App\Models\UnitProses;

class OrderPerbaikanController extends Controller
{
    private function getStatistics()
    {
        return [
            'totalOrders' => OrderPerbaikan::count(),
            'openOrders' => OrderPerbaikan::where('status', 'open')->count(),
            'inProgressOrders' => OrderPerbaikan::where('status', 'in_progress')->count(),
            'confirmedOrders' => OrderPerbaikan::where('status', 'confirmed')->count(),
            'rejectedOrders' => OrderPerbaikan::where('status', 'rejected')->count(),
            'rendahOrders' => OrderPerbaikan::where('prioritas', 'RENDAH')->count(),
            'sedangOrders' => OrderPerbaikan::where('prioritas', 'SEDANG')->count(),
            'tinggiOrders' => OrderPerbaikan::where('prioritas', 'TINGGI/URGENT')->count(),
        ];
    }

    public function index(Request $request)
    {
        $query = OrderPerbaikan::with(['creator', 'history', 'location']);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhere('nama_peminta', 'like', "%{$search}%");
            });
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('tanggal', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('tanggal', '<=', $request->date_to);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default to showing only open and in_progress orders when no status filter
            $query->whereIn('status', ['open', 'in_progress']);
        }
        
        // Apply priority filter
        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }
        
        // Apply location filter
        if ($request->filled('location_id')) {
            $query->where('lokasi', $request->location_id);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();
        
        // Get only locations that are used in existing orders
        $locationIds = OrderPerbaikan::whereNotNull('lokasi')
            ->distinct()
            ->pluck('lokasi')
            ->toArray();
        
        $locations = Location::whereIn('id', $locationIds)
            ->orderBy('name')
            ->get();

        return view('administrasi-umum.order-perbaikan.index', array_merge(
            [
                'orders' => $orders,
                'locations' => $locations
            ],
            $this->getStatistics()
        ));
    }

    public function filterOrders($type, $value)
    {
        $query = OrderPerbaikan::with(['creator', 'history']);

        switch ($type) {
            case 'status':
                $query->where('status', $value);
                break;
            case 'priority':
                $query->where('prioritas', $value);
                break;
            // Add more filter types as needed
        }

        $orders = $query->latest()->paginate(10);
        return view('administrasi-umum.order-perbaikan.index', array_merge(
            ['orders' => $orders],
            $this->getStatistics()
        ));
    }

    public function inProgress(Request $request)
    {
        $query = OrderPerbaikan::with(['creator', 'location'])
            ->where('status', 'in_progress');
            
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhere('nama_peminta', 'like', "%{$search}%")
                  ->orWhere('keluhan', 'like', "%{$search}%")
                  ->orWhere('kode_inventaris', 'like', "%{$search}%");
            });
        }
        
        // Apply priority filter
        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }
        
        // Apply location filter
        if ($request->filled('location_id')) {
            $query->where('lokasi', $request->location_id);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();
        $inProgressOrders = OrderPerbaikan::where('status', 'in_progress')->count();
        
        // Get only locations that are used in existing in-progress orders
        $locationIds = OrderPerbaikan::where('status', 'in_progress')
            ->whereNotNull('lokasi')
            ->distinct()
            ->pluck('lokasi')
            ->toArray();
        
        $locations = Location::whereIn('id', $locationIds)
            ->orderBy('name')
            ->get();

        if ($request->ajax()) {
            return view('administrasi-umum.order-perbaikan.in-progress', [
                'orders' => $orders,
                'inProgressOrders' => $inProgressOrders,
                'locations' => $locations
            ])->render();
        }

        return view('administrasi-umum.order-perbaikan.in-progress', [
            'orders' => $orders,
            'inProgressOrders' => $inProgressOrders,
            'locations' => $locations
        ]);
    }

    public function confirmed(Request $request)
    {
        $query = OrderPerbaikan::with(['creator', 'history'])
            ->where('status', 'confirmed');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhere('nama_peminta', 'like', "%{$search}%")
                  ->orWhere('keluhan', 'like', "%{$search}%");
            });
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('administrasi-umum.order-perbaikan.confirmed', [
                'orders' => $orders
            ])->render();
        }

        return view('administrasi-umum.order-perbaikan.confirmed', array_merge(
            ['orders' => $orders],
            $this->getStatistics()
        ));
    }

    public function rejected(Request $request)
    {
        $query = OrderPerbaikan::with(['creator', 'history'])
            ->where('status', 'rejected');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhere('nama_peminta', 'like', "%{$search}%")
                  ->orWhere('keluhan', 'like', "%{$search}%");
            });
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('administrasi-umum.order-perbaikan.rejected', [
                'orders' => $orders
            ])->render();
        }

        return view('administrasi-umum.order-perbaikan.rejected', array_merge(
            ['orders' => $orders],
            $this->getStatistics()
        ));
    }

    public function rendah()
    {
        return $this->filterOrders('prioritas', 'RENDAH');
    }

    public function sedang()
    {
        return $this->filterOrders('prioritas', 'SEDANG');
    }

    public function tinggi()
    {
        return $this->filterOrders('prioritas', 'TINGGI/URGENT');
    }

    public function show(OrderPerbaikan $orderPerbaikan)
    {
        $orderPerbaikan->load(['creator', 'history.creator', 'location']);
        
        // Direct to specific view based on status
        switch($orderPerbaikan->status) {
            case 'confirmed':
                return view('administrasi-umum.order-perbaikan.detail-confirmed', ['order' => $orderPerbaikan]);
            case 'rejected':
                return view('administrasi-umum.order-perbaikan.detail-rejected', ['order' => $orderPerbaikan]);
            default:
                return view('administrasi-umum.order-perbaikan.show', ['orderPerbaikan' => $orderPerbaikan]);
        }
    }

    public function updateStatus(Request $request, OrderPerbaikan $orderPerbaikan)
    {
        // Validate the request
        $request->validate([
            'status' => 'required|in:in_progress,confirmed,rejected',
            'follow_up' => 'required|string',
            'prioritas' => 'sometimes|required|in:RENDAH,SEDANG,TINGGI/URGENT',
        ]);

        try {
            DB::beginTransaction();

            // If status is changing from open to in_progress, require penanggung jawab
            if ($orderPerbaikan->status === 'open' && $request->status === 'in_progress') {
                $request->validate([
                    'nama_penanggung_jawab' => 'required|string',
                ]);

                $updateData = [
                    'status' => $request->status,
                    'nama_penanggung_jawab' => $request->nama_penanggung_jawab,
                    'follow_up' => $request->follow_up,
                    'updated_by' => auth()->id(),
                ];

                // Update prioritas if provided
                if ($request->has('prioritas')) {
                    $updateData['prioritas'] = $request->prioritas;
                }

                $orderPerbaikan->update($updateData);
            } else {
                // For other status updates, keep the existing penanggung jawab
                $updateData = [
                    'status' => $request->status,
                    'follow_up' => $request->follow_up,
                    'updated_by' => auth()->id(),
                ];

                // Update prioritas if provided
                if ($request->has('prioritas')) {
                    $updateData['prioritas'] = $request->prioritas;
                }

                $orderPerbaikan->update($updateData);
            }

            // Add history entry
            $orderPerbaikan->history()->create([
                'status' => $request->status,
                'keterangan' => $request->follow_up . ($request->has('prioritas') ? " (Prioritas diubah menjadi {$request->prioritas})" : ""),
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()
                ->route('administrasi-umum.order-perbaikan.show', $orderPerbaikan)
                ->with('success', 'Status order berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan saat memperbarui status: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function confirm(OrderPerbaikan $orderPerbaikan)
    {
        try {
            DB::beginTransaction();

            $orderPerbaikan->update([
                'status' => OrderPerbaikan::STATUS_CONFIRMED,
                'nama_penanggung_jawab' => auth()->user()->name,
                'updated_by' => auth()->id()
            ]);

            // Add history entry
            $orderPerbaikan->history()->create([
                'status' => OrderPerbaikan::STATUS_CONFIRMED,
                'keterangan' => 'Order dikonfirmasi',
                'created_by' => auth()->id()
            ]);

            DB::commit();

            return redirect()
                ->route('administrasi-umum.order-perbaikan.show', $orderPerbaikan)
                ->with('success', 'Order berhasil dikonfirmasi.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Terjadi kesalahan saat mengkonfirmasi order: ' . $e->getMessage());
        }
    }

    public function reject(OrderPerbaikan $orderPerbaikan)
    {
        try {
            DB::beginTransaction();

            $orderPerbaikan->update([
                'status' => OrderPerbaikan::STATUS_REJECTED,
                'nama_penanggung_jawab' => auth()->user()->name,
                'updated_by' => auth()->id()
            ]);

            // Add history entry
            $orderPerbaikan->history()->create([
                'status' => OrderPerbaikan::STATUS_REJECTED,
                'keterangan' => 'Order ditolak',
                'created_by' => auth()->id()
            ]);

            DB::commit();

            return redirect()
                ->route('administrasi-umum.order-perbaikan.show', $orderPerbaikan)
                ->with('success', 'Order berhasil ditolak.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Terjadi kesalahan saat menolak order: ' . $e->getMessage());
        }
    }

    public function complete(Request $request, OrderPerbaikan $orderPerbaikan)
    {
        try {
            DB::beginTransaction();

            $orderPerbaikan->update([
                'status' => 'completed',
                'updated_by' => auth()->id()
            ]);

            $orderPerbaikan->history()->create([
                'status' => 'completed',
                'follow_up' => $request->follow_up ?? 'Order telah selesai',
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('administrasi-umum.order-perbaikan.index')
                ->with('success', 'Order berhasil diselesaikan');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('administrasi-umum.order-perbaikan.index')
                ->with('error', 'Terjadi kesalahan saat menyelesaikan order: ' . $e->getMessage());
        }
    }

    public function start(OrderPerbaikan $orderPerbaikan)
    {
        DB::beginTransaction();
        try {
            $orderPerbaikan->update([
                'status' => 'in_progress',
                'nama_penanggung_jawab' => auth()->user()->name,
                'updated_by' => Auth::id(),
                'started_at' => now()
            ]);

            $orderPerbaikan->history()->create([
                'status' => 'in_progress',
                'follow_up' => 'Pengerjaan order dimulai',
                'created_by' => Auth::id()
            ]);

            $orderPerbaikan->creator->notify(new OrderPerbaikanStatusUpdated($orderPerbaikan));

            DB::commit();
            return redirect()->back()->with('success', 'Order berhasil dimulai.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function total(Request $request)
    {
        $query = OrderPerbaikan::with(['creator', 'history']);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhere('nama_peminta', 'like', "%{$search}%")
                  ->orWhere('keluhan', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        if ($request->ajax()) {
            return view('administrasi-umum.order-perbaikan.total', [
                'orders' => $orders
            ])->render();
        }

        return view('administrasi-umum.order-perbaikan.total', array_merge(
            ['orders' => $orders],
            $this->getStatistics()
        ));
    }

    public function export(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'status' => 'nullable|string|in:open,in_progress,completed,confirmed,rejected',
                'selected_ids' => 'nullable|string'
            ]);

            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $status = $request->input('status', 'confirmed');
            
            // Handle selected IDs if provided
            $selectedIds = [];
            if ($request->filled('selected_ids')) {
                $selectedIds = array_filter(
                    explode(',', $request->input('selected_ids')),
                    function($id) {
                        return is_numeric($id) && intval($id) > 0;
                    }
                );
                
                if (empty($selectedIds)) {
                    return back()->with('error', 'ID yang dipilih tidak valid.');
                }
                
                $selectedIds = array_map('intval', $selectedIds);
                
                // Verify the IDs exist in the database
                $existingIds = OrderPerbaikan::whereIn('id', $selectedIds)->pluck('id')->toArray();
                if (count($existingIds) !== count($selectedIds)) {
                    return back()->with('error', 'Beberapa ID yang dipilih tidak ditemukan.');
                }
            }

            // Build query to check data availability
            $query = OrderPerbaikan::query();
            if (!empty($selectedIds)) {
                $query->whereIn('id', $selectedIds);
            } else {
                if ($dateFrom) {
                    $query->whereDate('created_at', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('created_at', '<=', $dateTo);
                }
                $query->where('status', $status);
            }

            // Check if we have any data to export
            $count = $query->count();
            if ($count === 0) {
                return back()->with('error', 'Tidak ada data yang dapat diekspor dengan filter yang dipilih.');
            }

            // Log the export attempt
            \Log::info('Attempting export', [
                'user_id' => auth()->id(),
                'selected_ids' => $selectedIds,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'status' => $status,
                'count' => $count
            ]);

            $export = new OrderPerbaikanExport($dateFrom, $dateTo, $selectedIds, $status);
            return $export->download('order_perbaikan_' . now()->format('Y-m-d_His') . '.xlsx');
            
        } catch (\Exception $e) {
            \Log::error('Export error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'selected_ids' => $request->input('selected_ids'),
                'date_from' => $dateFrom ?? null,
                'date_to' => $dateTo ?? null,
                'status' => $status ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Terjadi kesalahan saat mengekspor data: ' . $e->getMessage());
        }
    }
} 
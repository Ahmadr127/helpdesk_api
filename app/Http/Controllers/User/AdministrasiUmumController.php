<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderPerbaikan;
use App\Models\OrderPerbaikanItem;
use App\Models\OrderPerbaikanHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Location;
use App\Models\UnitProses;
use App\Services\Api\OrderPerbaikanService;
use Illuminate\Support\Facades\Storage;

class AdministrasiUmumController extends Controller
{
    public function orderBarang(Request $request)
    {
        // Base query for in progress orders
        $inProgressQuery = OrderPerbaikan::with(['creator'])
            ->where('created_by', auth()->id())
            ->where('status', 'in_progress');

        // Add search for in progress orders
        if ($request->search) {
            $search = $request->search;
            $inProgressQuery->where(function($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhere('keluhan', 'like', "%{$search}%")
                  ->orWhere('kode_inventaris', 'like', "%{$search}%");
            });
        }

        // Add date filter for in progress orders
        if ($request->start_date && $request->end_date) {
            $inProgressQuery->whereBetween('tanggal', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $inProgressOrders = $inProgressQuery->latest()->take(6)->get();

        // Get open orders for table
        $query = OrderPerbaikan::with(['creator', 'history'])
            ->where('created_by', auth()->id())
            ->where('status', 'open');

        // Add search functionality
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhere('keluhan', 'like', "%{$search}%")
                  ->orWhere('kode_inventaris', 'like', "%{$search}%");
            });
        }

        // Filter tanggal for open orders
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('tanggal', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);
        $locations = Location::orderBy('name', 'asc')->get();
        $unitProses = UnitProses::where('status', 1)
            ->where('code', '!=', 'SIRS')
            ->get();

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('user.administrasi-umum.order-perbaikan._table', compact('orders'))->render(),
                'inProgressHtml' => view('user.administrasi-umum.order-perbaikan._in_progress_cards', compact('inProgressOrders'))->render()
            ]);
        }

        return view('user.administrasi-umum.order-barang', compact('orders', 'inProgressOrders', 'locations', 'unitProses'));
    }

    public function orderBarangKonfirmasi(Request $request)
    {
        $query = OrderPerbaikan::with(['creator', 'history'])
            ->where('created_by', auth()->id())
            ->where('status', 'confirmed');

        // Filter tanggal
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('tanggal', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('user.administrasi-umum.order-perbaikan._table', compact('orders'))->render()
            ]);
        }

        return view('user.administrasi-umum.order-barang-konfirmasi', compact('orders'));
    }

    public function orderBarangReject(Request $request)
    {
        $query = OrderPerbaikan::with(['creator', 'history'])
            ->where('created_by', auth()->id())
            ->where('status', 'rejected');

        // Filter tanggal
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('tanggal', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('user.administrasi-umum.order-perbaikan._table', compact('orders'))->render()
            ]);
        }

        return view('user.administrasi-umum.order-barang-reject', compact('orders'));
    }

    public function storeOrder(Request $request)
    {
        $request->validate([
            'nomor' => 'required|unique:order_perbaikan,nomor',
            'tanggal' => 'required|date',
            'unit_proses' => 'required',
            'unit_penerima' => 'required',
            'no_penerima' => 'required',
            'items' => 'required|array|min:1',
            'items.*.kode_inventaris' => 'required',
            'items.*.nama_barang' => 'required',
            'items.*.lokasi' => 'required',
            'items.*.jenis_barang' => 'required|in:Inventaris,Umum',
        ]);

        try {
            DB::beginTransaction();

            $order = OrderPerbaikan::create([
                'nomor' => $request->nomor,
                'tanggal' => $request->tanggal,
                'unit_proses' => $request->unit_proses,
                'unit_penerima' => $request->unit_penerima,
                'no_penerima' => $request->no_penerima,
                'status' => 'open',
                'created_by' => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                $order->items()->create($item);
            }

            // Create initial history
            $order->history()->create([
                'status' => 'open',
                'keterangan' => 'Order dibuat',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Order berhasil dibuat',
                'order' => $order->load('items'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat membuat order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateOrder(Request $request, OrderPerbaikan $order)
    {
        $request->validate([
            'nomor' => 'required|unique:order_perbaikan,nomor,' . $order->id,
            'tanggal' => 'required|date',
            'unit_proses' => 'required',
            'unit_penerima' => 'required',
            'no_penerima' => 'required',
            'items' => 'required|array|min:1',
            'items.*.kode_inventaris' => 'required',
            'items.*.nama_barang' => 'required',
            'items.*.lokasi' => 'required',
            'items.*.jenis_barang' => 'required|in:Inventaris,Umum',
        ]);

        try {
            DB::beginTransaction();

            $order->update([
                'nomor' => $request->nomor,
                'tanggal' => $request->tanggal,
                'unit_proses' => $request->unit_proses,
                'unit_penerima' => $request->unit_penerima,
                'no_penerima' => $request->no_penerima,
                'updated_by' => Auth::id(),
            ]);

            // Delete existing items and replace with new ones
            $order->items()->delete();
            foreach ($request->items as $item) {
                $order->items()->create($item);
            }

            // Add history entry for update
            $order->history()->create([
                'status' => $order->status,
                'keterangan' => 'Order diperbarui',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Order berhasil diperbarui',
                'order' => $order->load('items'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getOrder(OrderPerbaikan $order)
    {
        return response()->json($order->load(['items', 'history.creator', 'creator']));
    }

    public function searchOrders(Request $request)
    {
        $query = OrderPerbaikan::with(['items', 'creator']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('nomor', 'like', "%{$request->search}%")
                  ->orWhere('unit_proses', 'like', "%{$request->search}%")
                  ->orWhere('unit_penerima', 'like', "%{$request->search}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json($orders);
    }

    public function indexOrderPerbaikan(Request $request)
    {
        $status = $request->status ?? 'all';
        
        // Base query
        $query = OrderPerbaikan::with(['creator', 'history'])
            ->where('created_by', auth()->id());

        // Filter by status if specified
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Add search functionality
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%")
                  ->orWhere('keluhan', 'like', "%{$search}%")
                  ->orWhere('kode_inventaris', 'like', "%{$search}%");
            });
        }

        // Filter by date range
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('tanggal', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);
        $locations = Location::all();
        $unitProses = UnitProses::where('status', 1)
            ->where('code', '!=', 'SIRS')
            ->get();

        // Get statistics excluding soft-deleted records
        $stats = [
            'total' => OrderPerbaikan::where('created_by', auth()->id())->count(),
            'in_progress' => OrderPerbaikan::where('created_by', auth()->id())->where('status', 'in_progress')->count(),
            'confirmed' => OrderPerbaikan::where('created_by', auth()->id())->where('status', 'confirmed')->count(),
            'rejected' => OrderPerbaikan::where('created_by', auth()->id())->where('status', 'rejected')->count(),
        ];

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('user.administrasi-umum.order-perbaikan._table', compact('orders'))->render()
            ]);
        }

        return view('user.administrasi-umum.order-perbaikan.index', compact('orders', 'locations', 'unitProses', 'status', 'stats'));
    }

    public function createOrderPerbaikan()
    {
        try {
            // Generate nomor otomatis sesuai format: OP/RTG/MTC-YYYYMMDD001
            $currentDate = now();
            $prefix = 'OP/RTG/MTC-' . $currentDate->format('Ymd');
            
            // Get the last order number for today, including soft deleted records
            $lastOrder = OrderPerbaikan::withTrashed()
                ->where('nomor', 'like', $prefix . '%')
                ->orderBy('nomor', 'desc')
                ->first();

            if ($lastOrder) {
                $lastNumber = (int) substr($lastOrder->nomor, -3);
                $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '001';
            }

            $nomor = $prefix . $newNumber;
            $locations = Location::orderBy('name', 'asc')->get();
            $unitProses = UnitProses::where('status', 1)
                ->where('code', '!=', 'SIRS')
                ->get();

            // Get user data
            $user = auth()->user();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'nomor' => $nomor,
                        'tanggal' => $currentDate->format('Y-m-d H:i:s'),
                        'locations' => $locations,
                        'unitProses' => $unitProses,
                        'user' => [
                            'nip' => $user->nip,
                            'name' => $user->name
                        ]
                    ]
                ]);
            }

            return view('user.administrasi-umum.order-perbaikan.create', 
                compact('nomor', 'locations', 'unitProses', 'user'));

        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }

    public function storeOrderPerbaikan(Request $request, OrderPerbaikanService $service)
    {
        $validated = $request->validate([
            'unit_proses_code' => [
                'required',
                'exists:unit_proses,code',
                function ($attribute, $value, $fail) {
                    if ($value === 'SIRS') {
                        $fail('Unit proses SIRS tidak dapat dipilih untuk order barang.');
                    }
                },
            ],
            'jenis_barang' => 'required|in:Umum,Inventaris',
            'kode_inventaris' => 'required|string',
            'nama_barang' => 'required|string',
            'lokasi' => 'required|exists:locations,id',
            'keluhan' => 'required|string',
            'prioritas' => 'required|in:RENDAH,SEDANG,TINGGI/URGENT',
            'tanggal' => 'required|date',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // max 10MB
        ]);

        try {
            $orderPerbaikan = $service->create(auth()->user(), $validated, $request->file('foto'));

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order perbaikan berhasil dibuat dengan nomor: ' . $orderPerbaikan->nomor,
                    'data' => $orderPerbaikan
                ]);
            }

            return redirect()
                ->route('user.administrasi-umum.order-perbaikan.show', $orderPerbaikan)
                ->with('success', 'Order perbaikan berhasil dibuat dengan nomor: ' . $orderPerbaikan->nomor);

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat membuat order: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Terjadi kesalahan saat membuat order: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function showOrderPerbaikan(OrderPerbaikan $orderPerbaikan)
    {
        // Load the relationships we need
        $order = $orderPerbaikan->load(['history.creator', 'location', 'creator']);
        
        // Check if the current user is authorized to view this order
        if ($order->created_by !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('user.administrasi-umum.order-perbaikan.show', compact('order'));
    }

    public function editOrderPerbaikan(OrderPerbaikan $orderPerbaikan)
    {
        // Check if the current user is authorized to edit this order
        if ($orderPerbaikan->created_by !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        // Check if the order is still editable (open status)
        if ($orderPerbaikan->status !== 'open') {
            return redirect()->route('user.administrasi-umum.order-perbaikan.show', $orderPerbaikan)
                ->with('error', 'Hanya order dengan status open yang dapat diedit.');
        }

        $locations = Location::orderBy('name', 'asc')->get();
        return view('user.administrasi-umum.order-perbaikan.edit', compact('orderPerbaikan', 'locations'));
    }

    public function updateOrderPerbaikan(Request $request, OrderPerbaikan $orderPerbaikan, OrderPerbaikanService $service)
    {
        $validated = $request->validate([
            'jenis_barang' => 'required|in:Umum,Inventaris',
            'kode_inventaris' => 'required|string',
            'nama_barang' => 'required|string',
            'lokasi' => 'required|exists:locations,id',
            'keluhan' => 'required|string',
            'prioritas' => 'required|in:RENDAH,SEDANG,TINGGI/URGENT',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // max 10MB
        ]);

        try {
            $order = $service->update(auth()->user(), $orderPerbaikan, $validated, $request->file('foto'));

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order perbaikan berhasil diperbarui',
                    'data' => $order
                ]);
            }

            return redirect()
                ->route('user.administrasi-umum.order-perbaikan.show', $order)
                ->with('success', 'Order perbaikan berhasil diperbarui');

        } catch (\Exception $e) {
            $code = $e->getCode() === 403 ? 403 : ($e->getCode() === 422 ? 422 : 500);
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], $code);
            }
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function deleteOrderPerbaikan(OrderPerbaikan $orderPerbaikan, OrderPerbaikanService $service)
    {
        try {
            $service->delete(auth()->user(), $orderPerbaikan);
            return response()->json([
                'success' => true,
                'message' => 'Order perbaikan berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode() === 403 ? 403 : ($e->getCode() === 422 ? 422 : 500);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $code);
        }
    }
}